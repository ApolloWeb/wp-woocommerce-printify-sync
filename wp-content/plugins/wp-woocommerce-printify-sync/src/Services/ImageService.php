<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImageService
{
    private string $currentTime = '2025-03-15 19:08:44';
    private string $currentUser = 'ApolloWeb';
    private R2StorageService $r2Service;

    public function __construct()
    {
        $this->r2Service = new R2StorageService();
    }

    public function handleProductImages(int $productId, array $images): void
    {
        $product = wc_get_product($productId);
        if (!$product) return;

        $existingImages = $this->getExistingImages($productId);
        $newImages = [];
        $position = 0;

        foreach ($images as $image) {
            try {
                // Check if image already exists
                $imageId = $this->findExistingImage($image['src'], $existingImages);
                
                if (!$imageId) {
                    // Download and process new image
                    $imageId = $this->processImage($image['src'], $productId);
                }

                if ($imageId) {
                    $newImages[] = $imageId;
                    
                    // Set featured image
                    if ($position === 0) {
                        set_post_thumbnail($productId, $imageId);
                    }
                    
                    // Store additional image data
                    update_post_meta($imageId, '_wpwps_image_position', $position);
                    update_post_meta($imageId, '_wpwps_printify_image_id', $image['id']);
                }

                $position++;

            } catch (\Exception $e) {
                error_log("Image processing failed: " . $e->getMessage());
                continue;
            }
        }

        // Update product gallery
        if (count($newImages) > 1) {
            $product->set_gallery_image_ids(array_slice($newImages, 1));
        }
        
        $product->save();

        // Clean up old images
        $this->cleanupOldImages($existingImages, $newImages);
    }

    private function processImage(string $imageUrl, int $productId): int
    {
        // Download image
        $tempFile = download_url($imageUrl);
        if (is_wp_error($tempFile)) {
            throw new \Exception($tempFile->get_error_message());
        }

        // Prepare file array
        $file = [
            'name' => basename($imageUrl),
            'tmp_name' => $tempFile,
            'type' => wp_check_filetype(basename($imageUrl))['type']
        ];

        // Upload to R2 if enabled
        if (get_option('wpwps_use_r2_storage', false)) {
            $r2Url = $this->r2Service->uploadImage($tempFile, $file['name']);
            
            // Create attachment without locally storing the file
            $attachment = [
                'post_mime_type' => $file['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', $file['name']),
                'post_content' => '',
                'post_status' => 'inherit',
                'guid' => $r2Url
            ];

            $attachId = wp_insert_attachment($attachment, $r2Url, $productId);
            
            // Generate metadata without local file
            update_post_meta($attachId, '_wp_attached_file', basename($r2Url));
            update_post_meta($attachId, '_wpwps_r2_url', $r2Url);

        } else {
            // Standard WordPress media handling
            $attachId = media_handle_sideload($file, $productId);
        }

        // Cleanup
        @unlink($tempFile);

        if (is_wp_error($attachId)) {
            throw new \Exception($attachId->get_error_message());
        }

        return $attachId;
    }

    private function getExistingImages(int $productId): array
    {
        $product = wc_get_product($productId);
        if (!$product) return [];

        $images = [];
        
        // Get featured image
        if ($product->get_image_id()) {
            $images[] = $product->get_image_id();
        }
        
        // Get gallery images
        $images = array_merge($images, $product->get_gallery_image_ids());

        return array_unique($images);
    }

    private function findExistingImage(string $imageUrl, array $existingImages): ?int
    {
        foreach ($existingImages as $imageId) {
            $metadata = wp_get_attachment_metadata($imageId);
            if (isset($metadata['original_url']) && $metadata['original_url'] === $imageUrl) {
                return $imageId;
            }
        }
        return null;
    }

    private function cleanupOldImages(array $oldImages, array $newImages): void
    {
        $imagesToDelete = array_diff($oldImages, $newImages);
        
        foreach ($imagesToDelete as $imageId) {
            // Check if image is stored in R2
            $r2Url = get_post_meta($imageId, '_wpwps_r2_url', true);
            if ($r2Url) {
                $this->r2Service->deleteImage(basename($r2Url));
            }
            
            // Delete attachment
            wp_delete_attachment($imageId, true);
        }
    }
}