<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImageOptimizationService
{
    private string $currentTime = '2025-03-15 19:22:36';
    private string $currentUser = 'ApolloWeb';
    private bool $smushEnabled;
    private array $optimizationSettings;

    public function __construct()
    {
        $this->smushEnabled = class_exists('WP_Smush');
        $this->optimizationSettings = [
            'max_width' => (int) get_option('wpwps_max_image_width', 2048),
            'max_height' => (int) get_option('wpwps_max_image_height', 2048),
            'jpeg_quality' => (int) get_option('wpwps_jpeg_quality', 82),
            'strip_metadata' => (bool) get_option('wpwps_strip_metadata', true),
            'preserve_animation' => (bool) get_option('wpwps_preserve_animation', true),
        ];

        // Add hooks for optimization
        add_filter('wp_handle_upload', [$this, 'optimizeUploadedImage'], 9); // Before SMUSH
        add_action('add_attachment', [$this, 'queueOptimization']);
        add_action('wpwps_optimize_image', [$this, 'optimizeImage']);
    }

    public function optimizeUploadedImage(array $upload): array
    {
        if (!$this->isImageFile($upload['type'])) {
            return $upload;
        }

        try {
            $optimized = $this->performOptimization($upload['file']);
            
            if ($optimized) {
                // Update file size in upload data
                $upload['size'] = filesize($upload['file']);
            }

        } catch (\Exception $e) {
            error_log("Image optimization failed: " . $e->getMessage());
        }

        return $upload;
    }

    public function queueOptimization(int $attachmentId): void
    {
        if (!$this->isImageAttachment($attachmentId)) {
            return;
        }

        // Queue for async optimization
        wp_schedule_single_event(
            time() + 10,
            'wpwps_optimize_image',
            [$attachmentId]
        );
    }

    public function optimizeImage(int $attachmentId): void
    {
        try {
            $file = get_attached_file($attachmentId);
            if (!$file || !file_exists($file)) {
                return;
            }

            $originalSize = filesize($file);
            $optimized = $this->performOptimization($file);

            if ($optimized) {
                $newSize = filesize($file);
                $savings = $originalSize - $newSize;

                // Store optimization results
                update_post_meta($attachmentId, '_wpwps_optimization_savings', $savings);
                update_post_meta($attachmentId, '_wpwps_optimized_at', $this->currentTime);
                update_post_meta($attachmentId, '_wpwps_optimized_by', $this->currentUser);

                // Handle thumbnails
                $this->optimizeThumbnails($attachmentId);
            }

        } catch (\Exception $e) {
            error_log("Image optimization failed for attachment $attachmentId: " . $e->getMessage());
            update_post_meta($attachmentId, '_wpwps_optimization_error', $e->getMessage());
        }
    }

    private function performOptimization(string $file): bool
    {
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            throw new \Exception('No image processing library available');
        }

        $type = mime_content_type($file);
        if (!$this->isImageFile($type)) {
            return false;
        }

        // Use Imagick if available, fall back to GD
        if (extension_loaded('imagick')) {
            return $this->optimizeWithImagick($file);
        }

        return $this->optimizeWithGD($file);
    }

    private function optimizeWithImagick(string $file): bool
    {
        try {
            $image = new \Imagick($file);

            // Preserve animation for GIFs if configured
            if ($image->getImageFormat() === 'GIF' && $this->optimizationSettings['preserve_animation']) {
                return $this->optimizeAnimatedGif($image, $file);
            }

            // Strip metadata if configured
            if ($this->optimizationSettings['strip_metadata']) {
                $image->stripImage();
            }

            // Resize if needed
            $this->resizeImage($image);

            // Set compression quality
            $image->setImageCompressionQuality($this->optimizationSettings['jpeg_quality']);

            // Optimize for web
            $image->setInterlaceScheme(\Imagick::INTERLACE_PLANE);

            // Save optimized image
            $image->writeImage($file);
            $image->clear();

            return true;

        } catch (\ImagickException $e) {
            throw new \Exception("Imagick optimization failed: " . $e->getMessage());
        }
    }

    private function optimizeWithGD(string $file): bool
    {
        $type = mime_content_type($file);
        $image = $this->createImageFromFile($file, $type);

        if (!$image) {
            return false;
        }

        // Get original dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Calculate new dimensions if needed
        list($newWidth, $newHeight) = $this->calculateDimensions($width, $height);

        // Create new image if resize needed
        if ($newWidth !== $width || $newHeight !== $height) {
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency
            if ($type === 'image/png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
            }

            imagecopyresampled(
                $newImage, $image,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $width, $height
            );

            imagedestroy($image);
            $image = $newImage;
        }

        // Save optimized image
        return $this->saveImage($image, $file, $type);
    }

    private function optimizeThumbnails(int $attachmentId): void
    {
        $metadata = wp_get_attachment_metadata($attachmentId);
        if (empty($metadata['sizes'])) {
            return;
        }

        $baseDir = dirname(get_attached_file($attachmentId));

        foreach ($metadata['sizes'] as $size => $data) {
            $thumbPath = $baseDir . '/' . $data['file'];
            
            if (file_exists($thumbPath)) {
                try {
                    $this->performOptimization($thumbPath);
                } catch (\Exception $e) {
                    error_log("Thumbnail optimization failed: " . $e->getMessage());
                }
            }
        }
    }

    private function calculateDimensions(int $width, int $height): array
    {
        $maxWidth = $this->optimizationSettings['max_width'];
        $maxHeight = $this->optimizationSettings['max_height'];

        if ($width <= $maxWidth && $height <= $maxHeight) {
            return [$width, $height];
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);
        return [
            (int) round($width * $ratio),
            (int) round($height * $ratio)
        ];
    }

    private function isImageFile(string $mimeType): bool
    {
        return strpos($mimeType, 'image/') === 0;
    }

    private function isImageAttachment(int $attachmentId): bool
    {
        return strpos(get_post_mime_type($attachmentId), 'image/') === 0;
    }

    private function createImageFromFile(string $file, string $type)
    {
        switch ($type) {
            case 'image/jpeg':
                return imagecreatefromjpeg($file);
            case 'image/png':
                return imagecreatefrompng($file);
            case 'image/gif':
                return imagecreatefromgif($file);
            case 'image/webp':
                return imagecreatefromwebp($file);
            default:
                return false;
        }
    }

    private function saveImage($image, string $file, string $type): bool
    {
        switch ($type) {
            case 'image/jpeg':
                return imagejpeg($image, $file, $this->optimizationSettings['jpeg_quality']);
            case 'image/png':
                return imagepng($image, $file, 9); // Maximum PNG compression
            case 'image/gif':
                return imagegif($image, $file);
            case 'image/webp':
                return imagewebp($image, $file, $this->optimizationSettings['jpeg_quality']);
            default:
                return false;
        }
    }

    private function optimizeAnimatedGif(\Imagick $image, string $file): bool
    {
        $image = $image->coalesceImages();
        
        do {
            $this->resizeImage($image);
        } while ($image->nextImage());

        $image = $image->deconstructImages();
        $image->writeImages($file, true);
        $image->clear();

        return true;
    }

    private function resizeImage(\Imagick $image): void
    {
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();

        list($newWidth, $newHeight) = $this->calculateDimensions($width, $height);

        if ($newWidth !== $width || $newHeight !== $height) {
            $image->resizeImage($newWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1);
        }
    }
}