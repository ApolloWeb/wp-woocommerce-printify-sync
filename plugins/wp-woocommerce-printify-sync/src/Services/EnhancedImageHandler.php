<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\ImageHandlerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\StorageInterface;

class EnhancedImageHandler extends AbstractService implements ImageHandlerInterface
{
    private StorageInterface $storage;
    private array $imageHashes = [];
    private string $uploadsDir;
    private string $uploadsUrl;

    public function __construct(
        StorageInterface $storage,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        parent::__construct($logger, $config);
        $this->storage = $storage;
        
        $uploadDirs = wp_upload_dir();
        $this->uploadsDir = $uploadDirs['basedir'];
        $this->uploadsUrl = $uploadDirs['baseurl'];
    }

    public function processProductImage(
        string $url,
        int $productId,
        array $metadata = []
    ): ?int {
        try {
            // Generate hash for image URL
            $imageHash = md5($url);
            
            // Check if image has changed
            $existingAttachment = $this->findExistingAttachment($imageHash);
            if ($existingAttachment) {
                return $existingAttachment;
            }

            // Download and optimize image
            $tempFile = $this->downloadImage($url);
            if (!$tempFile) {
                return null;
            }

            $optimizedFile = $this->optimizeImage($tempFile);
            $finalFile = $optimizedFile ?? $tempFile;

            // Store image
            if ($this->config->get('use_r2_storage', false)) {
                $attachmentId = $this->handleR2Storage($finalFile, $productId, $imageHash, $metadata);
            } else {
                $attachmentId = $this->handleLocalStorage($finalFile, $productId, $imageHash, $metadata);
            }

            // Cleanup
            @unlink($tempFile);
            if ($optimizedFile) {
                @unlink($optimizedFile);
            }

            return $attachmentId;

        } catch (\Exception $e) {
            $this->logError('processProductImage', $e, [
                'url' => $url,
                'product_id' => $productId
            ]);
            return null;
        }
    }

    private function findExistingAttachment(string $imageHash): ?int
    {
        global $wpdb;
        
        $attachmentId = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
            WHERE meta_key = '_printify_image_hash' 
            AND meta_value = %s 
            LIMIT 1",
            $imageHash
        ));

        return $attachmentId ? (int)$attachmentId : null;
    }

    private function handleR2Storage(
        string $file,
        int $productId,
        string $imageHash,
        array $metadata
    ): ?int {
        // Generate R2 path
        $r2Path = sprintf(
            'products/%d/%s/%s',
            $productId,
            date('Y/m'),
            basename($file)
        );

        // Upload to R2
        if (!$this->storage->put($r2Path, file_get_contents($file))) {
            throw new \Exception('Failed to upload to R2');
        }

        // Create attachment
        $attachment = [
            'post_mime_type' => wp_check_filetype($file)['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($file)),
            'post_content' => '',
            'post_status' => 'inherit',
            'guid' => $this->storage->url($r2Path)
        ];

        $attachmentId = wp_insert_attachment($attachment, $r2Path, $productId);
        if (is_wp_error($attachmentId)) {
            throw new \Exception($attachmentId->get_error_message());
        }

        // Add custom metadata
        update_post_meta($attachmentId, '_printify_image_hash', $imageHash);
        update_post_meta($attachmentId, '_printify_storage', 'r2');
        update_post_meta($attachmentId, '_printify_r2_path', $r2Path);
        
        foreach ($metadata as $key => $value) {
            update_post_meta($attachmentId, '_printify_' . $key, $value);
        }

        return $attachmentId;
    }

    private function handleLocalStorage(
        string $file,
        int $productId,
        string $imageHash,
        array $metadata
    ): ?int {
        // Generate local path
        $localPath = sprintf(
            '%s/printify/%d/%s/%s',
            $this->uploadsDir,
            $productId,
            date('Y/m'),
            basename($file)
        );

        // Ensure directory exists
        wp_mkdir_p(dirname($localPath));

        // Copy file
        if (!copy($file, $localPath)) {
            throw new \Exception('Failed to copy file to local storage');
        }

        // Create attachment
        $attachment = [
            'post_mime_type' => wp_check_filetype($file)['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($file)),
            'post_content' => '',
            'post_status' => 'inherit',
            'guid' => str_replace($this->uploadsDir, $this->uploadsUrl, $localPath)
        ];

        $attachmentId = wp_insert_attachment($attachment, $localPath, $productId);
        if (is_wp_error($attachmentId)) {
            throw new \Exception($attachmentId->get_error_message());
        }

        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachmentData = wp_generate_attachment_metadata($attachmentId, $localPath);
        wp_update_attachment_metadata($attachmentId, $attachmentData);

        // Add custom metadata
        update_post_meta($attachmentId, '_printify_image_hash', $imageHash);
        update_post_meta($attachmentId, '_printify_storage', 'local');
        update_post_meta($attachmentId, '_printify_local_path', $localPath);
        
        foreach ($metadata as $key => $value) {
            update_post_meta($attachmentId, '_printify_' . $key, $value);
        }

        return $attachmentId;
    }
}