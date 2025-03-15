<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImageUploadHandler
{
    private string $currentTime = '2025-03-15 19:19:55';
    private string $currentUser = 'ApolloWeb';
    private bool $useR2;
    private bool $smushEnabled;
    private string $uploadDir;
    private array $allowedMimeTypes;
    private int $maxFileSize;

    public function __construct()
    {
        $this->useR2 = (bool) get_option('wpwps_use_r2_storage', false);
        $this->smushEnabled = class_exists('WP_Smush');
        $this->uploadDir = 'printify-products/' . date('Y/m', strtotime($this->currentTime));
        $this->allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB

        // Hooks for upload handling
        add_filter('upload_dir', [$this, 'modifyUploadDir']);
        add_filter('wp_handle_upload_prefilter', [$this, 'validateUpload']);
        add_action('add_attachment', [$this, 'handleNewUpload']);
    }

    public function uploadImage(string $sourceUrl, array $metadata = []): ?int
    {
        try {
            // Download image to temp location
            $tempFile = $this->downloadImage($sourceUrl);
            if (!$tempFile) {
                throw new \Exception("Failed to download image from $sourceUrl");
            }

            // Validate file
            $this->validateFile($tempFile);

            // Generate unique filename
            $filename = $this->generateUniqueFilename($sourceUrl);

            // Prepare file data
            $file = [
                'name' => $filename,
                'tmp_name' => $tempFile,
                'type' => mime_content_type($tempFile),
                'error' => 0,
                'size' => filesize($tempFile)
            ];

            // Add to media library
            $attachmentId = $this->addToMediaLibrary($file, $metadata);

            // Process with Smush if available
            if ($this->smushEnabled) {
                do_action('wp_smush_async_optimize_image', $attachmentId);
            }

            // Handle R2 storage if enabled
            if ($this->useR2) {
                $this->handleR2Upload($attachmentId);
            }

            return $attachmentId;

        } catch (\Exception $e) {
            error_log("Image upload failed: " . $e->getMessage());
            return null;
        } finally {
            // Cleanup temp file
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    private function downloadImage(string $url): ?string
    {
        $tempFile = download_url($url);
        if (is_wp_error($tempFile)) {
            return null;
        }
        return $tempFile;
    }

    private function validateFile(string $filePath): void
    {
        // Check file size
        if (filesize($filePath) > $this->maxFileSize) {
            throw new \Exception('File size exceeds maximum limit');
        }

        // Check mime type
        $mimeType = mime_content_type($filePath);
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new \Exception('Invalid file type');
        }

        // Validate image dimensions
        $imageSize = getimagesize($filePath);
        if (!$imageSize) {
            throw new \Exception('Invalid image file');
        }

        // Additional security checks
        if (!$this->isSecureImage($filePath)) {
            throw new \Exception('Image security check failed');
        }
    }

    private function isSecureImage(string $filePath): bool
    {
        // Check for PHP code in image
        $content = file_get_contents($filePath);
        if (preg_match('/<\?php/i', $content)) {
            return false;
        }

        // Check for suspicious metadata
        $imagine = new \Imagine\Gd\Imagine();
        try {
            $metadata = $imagine->open($filePath)->metadata();
            $suspiciousKeys = ['php', 'script', 'exec'];
            
            foreach ($suspiciousKeys as $key) {
                if (stripos(json_encode($metadata), $key) !== false) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    private function addToMediaLibrary(array $file, array $metadata): int
    {
        $upload = wp_handle_sideload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            throw new \Exception($upload['error']);
        }

        $attachmentData = array_merge([
            'post_mime_type' => $upload['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
            'post_content' => '',
            'post_status' => 'inherit'
        ], $metadata);

        $attachmentId = wp_insert_attachment($attachmentData, $upload['file']);
        
        if (is_wp_error($attachmentId)) {
            throw new \Exception($attachmentId->get_error_message());
        }

        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        wp_update_attachment_metadata(
            $attachmentId,
            wp_generate_attachment_metadata($attachmentId, $upload['file'])
        );

        return $attachmentId;
    }

    private function handleR2Upload(int $attachmentId): void
    {
        try {
            $file = get_attached_file($attachmentId);
            if (!$file) {
                throw new \Exception('Attachment file not found');
            }

            // Upload to R2
            $r2Service = new R2StorageService();
            $r2Url = $r2Service->uploadImage($file, basename($file));

            // Update attachment metadata
            update_post_meta($attachmentId, '_wpwps_r2_url', $r2Url);
            update_post_meta($attachmentId, '_wpwps_is_r2', true);
            update_post_meta($attachmentId, '_wpwps_uploaded_at', $this->currentTime);
            update_post_meta($attachmentId, '_wpwps_uploaded_by', $this->currentUser);

            // Handle thumbnails
            $metadata = wp_get_attachment_metadata($attachmentId);
            if (!empty($metadata['sizes'])) {
                $this->handleThumbnailsR2Upload($attachmentId, $metadata);
            }

            // Remove local file if configured
            if (get_option('wpwps_remove_local_after_r2', true)) {
                @unlink($file);
            }

        } catch (\Exception $e) {
            error_log("R2 upload failed for attachment $attachmentId: " . $e->getMessage());
            update_post_meta($attachmentId, '_wpwps_r2_error', $e->getMessage());
        }
    }

    private function handleThumbnailsR2Upload(int $attachmentId, array $metadata): void
    {
        $baseDir = dirname(get_attached_file($attachmentId));
        $r2Service = new R2StorageService();

        foreach ($metadata['sizes'] as $size => $sizeData) {
            $thumbPath = $baseDir . '/' . $sizeData['file'];
            
            if (file_exists($thumbPath)) {
                try {
                    $r2Url = $r2Service->uploadImage($thumbPath, $sizeData['file']);
                    $metadata['sizes'][$size]['r2_url'] = $r2Url;

                    if (get_option('wpwps_remove_local_after_r2', true)) {
                        @unlink($thumbPath);
                    }
                } catch (\Exception $e) {
                    error_log("R2 thumbnail upload failed: " . $e->getMessage());
                }
            }
        }

        wp_update_attachment_metadata($attachmentId, $metadata);
    }

    public function modifyUploadDir(array $uploads): array
    {
        $uploads['subdir'] = '/' . $this->uploadDir;
        $uploads['path'] = $uploads['basedir'] . $uploads['subdir'];
        $uploads['url'] = $uploads['baseurl'] . $uploads['subdir'];
        return $uploads;
    }

    private function generateUniqueFilename(string $originalUrl): string
    {
        $ext = pathinfo($originalUrl, PATHINFO_EXTENSION);
        if (!$ext) {
            $ext = 'jpg';
        }

        $basename = pathinfo($originalUrl, PATHINFO_FILENAME);
        $cleanName = sanitize_file_name($basename);
        $uniqueId = substr(md5(uniqid() . $this->currentTime), 0, 8);
        
        return sprintf(
            '%s-%s-%s.%s',
            $cleanName,
            $this->currentTime,
            $uniqueId,
            $ext
        );
    }
}