<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImageHandler
{
    private string $currentTime = '2025-03-15 20:00:16';
    private string $currentUser = 'ApolloWeb';

    public function handleImage(string $imageUrl, int $productId): int
    {
        // Check if image already exists
        $existingImageId = $this->findExistingImage($imageUrl);
        if ($existingImageId) {
            return $existingImageId;
        }

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Download and process image
        try {
            // Add user agent to avoid blocked requests
            add_filter('http_request_args', [$this, 'addUserAgent'], 10, 2);
            
            $temp = download_url($imageUrl);
            
            remove_filter('http_request_args', [$this, 'addUserAgent']);

            if (is_wp_error($temp)) {
                throw new \Exception($temp->get_error_message());
            }

            // Prepare file array
            $file = [
                'name' => $this->generateImageName($imageUrl),
                'tmp_name' => $temp,
                'error' => 0,
                'size' => filesize($temp)
            ];

            // Handle image optimization if enabled
            if (get_option('wpwps_optimize_images', true)) {
                $this->optimizeImage($temp);
            }

            // Upload and attach to product
            $imageId = media_handle_sideload($file, $productId);

            if (is_wp_error($imageId)) {
                unlink($temp);
                throw new \Exception($imageId->get_error_message());
            }

            // Store original URL for future reference
            update_post_meta($imageId, '_printify_original_url', $imageUrl);
            update_post_meta($imageId, '_printify_import_date', $this->currentTime);
            update_post_meta($imageId, '_printify_imported_by', $this->currentUser);

            return $imageId;

        } catch (\Exception $e) {
            error_log(sprintf(
                '[WPWPS] Image import failed: %s - URL: %s - Time: %s',
                $e->getMessage(),
                $imageUrl,
                $this->currentTime
            ));
            throw $e;
        }
    }

    private function findExistingImage(string $imageUrl): ?int
    {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_original_url' 
            AND meta_value = %s 
            LIMIT 1",
            $imageUrl
        ));
    }

    private function generateImageName(string $url): string
    {
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        return sprintf(
            'printify-product-%s.%s',
            uniqid(),
            $extension ?: 'jpg'
        );
    }

    private function optimizeImage(string $path): void
    {
        if (!extension_loaded('gd')) {
            return;
        }

        $maxWidth = (int) get_option('wpwps_max_image_width', 2048);
        $quality = (int) get_option('wpwps_image_quality', 82);

        $info = getimagesize($path);
        if (!$info) {
            return;
        }

        [$width, $height, $type] = $info;

        // Skip if image is already smaller
        if ($width <= $maxWidth) {
            return;
        }

        // Calculate new dimensions
        $ratio = $maxWidth / $width;
        $newWidth = $maxWidth;
        $newHeight = (int) ($height * $ratio);

        // Create new image
        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => false
        };

        if (!$source) {
            return;
        }

        $destination = imagecreatetruecolor($newWidth, $newHeight);

        // Handle transparency for PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
        }

        // Resize
        imagecopyresampled(
            $destination,
            $source,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        // Save optimized image
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($destination, $path, $quality);
                break;
            case IMAGETYPE_PNG:
                imagepng($destination, $path, 9);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($destination, $path, $quality);
                break;
        }

        imagedestroy($source);
        imagedestroy($destination);
    }

    public function addUserAgent($args, $url): array
    {
        $args['user-agent'] = 'Mozilla/5.0 (Printify WooCommerce Sync)';
        return $args;
    }

    public function cleanup(): void
    {
        global $wpdb;

        // Find orphaned images (not attached to any product)
        $orphanedImages = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts} p
            WHERE p.post_type = 'attachment'
            AND EXISTS (
                SELECT 1 FROM {$wpdb->postmeta}
                WHERE post_id = p.ID
                AND meta_key = '_printify_original_url'
            )
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->posts}
                WHERE post_type = 'product'
                AND (
                    ID = p.post_parent
                    OR ID IN (
                        SELECT post_id FROM {$wpdb->postmeta}
                        WHERE meta_key = '_thumbnail_id'
                        AND meta_value = p.ID
                    )
                    OR ID IN (
                        SELECT post_id FROM {$wpdb->postmeta}
                        WHERE meta_key = '_product_image_gallery'
                        AND FIND_IN_SET(p.ID, meta_value)
                    )
                )
            )
        ");

        foreach ($orphanedImages as $imageId) {
            wp_delete_attachment($imageId, true);
        }
    }
}