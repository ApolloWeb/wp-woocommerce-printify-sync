<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class MediaLibraryService
{
    private string $currentTime = '2025-03-15 19:16:40';
    private string $currentUser = 'ApolloWeb';
    private bool $useR2;
    private bool $smushEnabled;

    public function __construct()
    {
        $this->useR2 = (bool) get_option('wpwps_use_r2_storage', false);
        $this->smushEnabled = class_exists('WP_Smush');

        // Wait for Smush to complete before R2 offload
        add_action('wp_smush_image_optimised', [$this, 'handleSmushComplete'], 10, 2);
        
        // Handle WebP generation
        add_action('wp_smush_webp_converted', [$this, 'handleWebPConverted'], 10, 2);
    }

    public function handleSmushComplete(array $response, int $attachmentId): void
    {
        if ($this->useR2) {
            // Only offload to R2 after Smush has completed
            update_post_meta($attachmentId, '_wpwps_ready_for_r2', true);
            $this->maybeOffloadToR2($attachmentId);
        }
    }

    public function handleWebPConverted(string $path, int $attachmentId): void
    {
        if ($this->useR2) {
            // Upload WebP version to R2
            $webpUrl = $this->r2Service->uploadImage($path, basename($path));
            update_post_meta($attachmentId, '_wpwps_r2_webp_url', $webpUrl);
            @unlink($path); // Remove local WebP file after R2 upload
        }
    }

    private function maybeOffloadToR2(int $attachmentId): void
    {
        // Check if image is ready for R2 offload
        if (!get_post_meta($attachmentId, '_wpwps_ready_for_r2', true)) {
            return;
        }

        // Check if WebP conversion is pending
        if ($this->smushEnabled && !get_post_meta($attachmentId, '_wp_smush_webp_done', true)) {
            return;
        }

        $this->offloadToR2($attachmentId);
        delete_post_meta($attachmentId, '_wpwps_ready_for_r2');
    }

    public function importPrintifyImages(array $images, int $productId): array
    {
        $importedImages = [];
        $position = 0;

        foreach ($images as $image) {
            try {
                $attachmentId = $this->importSingleImage($image, $productId, $position);
                
                if ($attachmentId) {
                    $importedImages[] = $attachmentId;
                    
                    // Set as featured image if it's the first image
                    if ($position === 0) {
                        set_post_thumbnail($productId, $attachmentId);
                    }
                    
                    $position++;
                }

            } catch (\Exception $e) {
                error_log("Failed to import Printify image: " . $e->getMessage());
                continue;
            }
        }

        // Update product gallery
        if (count($importedImages) > 1) {
            update_post_meta($productId, '_product_image_gallery', implode(',', array_slice($importedImages, 1)));
        }

        return $importedImages;
    }

    private function importSingleImage(array $image, int $productId, int $position): ?int
    {
        // Download image
        $tempFile = download_url($image['src']);
        if (is_wp_error($tempFile)) {
            return null;
        }

        try {
            // Prepare file data
            $filename = $this->generateUniqueFilename($image['src']);
            $file = [
                'name' => $filename,
                'tmp_name' => $tempFile,
                'type' => 'image/jpeg'
            ];

            // Add to media library
            $attachmentId = media_handle_sideload($file, $productId, '', [
                'post_title' => $image['title'] ?? '',
                'post_content' => $image['description'] ?? '',
            ]);

            if (is_wp_error($attachmentId)) {
                throw new \Exception($attachmentId->get_error_message());
            }

            // Store Printify metadata
            $this->storeImageMetadata($attachmentId, $image, $position);

            // Let Smush process the image
            if ($this->smushEnabled) {
                do_action('wp_smush_async_optimize_image', $attachmentId);
            } else {
                // If Smush is not active, proceed with R2 offload directly
                if ($this->useR2) {
                    $this->offloadToR2($attachmentId);
                }
            }

            return $attachmentId;

        } finally {
            @unlink($tempFile);
        }

        return null;
    }

    private function storeImageMetadata(int $attachmentId, array $image, int $position): void
    {
        $metadata = [
            '_wpwps_printify_image_id' => $image['id'],
            '_wpwps_printify_position' => $position,
            '_wpwps_printify_src' => $image['src'],
            '_wpwps_printify_imported_at' => $this->currentTime,
            '_wpwps_printify_imported_by' => $this->currentUser,
            '_wpwps_storage_type' => $this->useR2 ? 'r2' : 'local'
        ];

        foreach ($metadata as $key => $value) {
            update_post_meta($attachmentId, $key, $value);
        }
    }
}