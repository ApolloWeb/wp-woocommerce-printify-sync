<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Handlers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ActionScheduler\ImageImportTask;

class ProductImageHandler
{
    /**
     * Schedule image import using Action Scheduler
     *
     * @param int $productId
     * @param array $images
     * @return void
     */
    public function scheduleImageImport(int $productId, array $images): void
    {
        if (empty($images)) {
            return;
        }
        
        // Store image data in transient for batch processing
        $transientKey = 'wpwps_product_images_' . $productId;
        set_transient($transientKey, $images, DAY_IN_SECONDS);
        
        // Schedule the task
        $task = new ImageImportTask();
        $task->schedule($productId);
    }
    
    /**
     * Process image import for a product
     * Called by ActionScheduler task
     *
     * @param int $productId
     * @return bool
     */
    public function processImageImport(int $productId): bool
    {
        $transientKey = 'wpwps_product_images_' . $productId;
        $images = get_transient($transientKey);
        
        if (!$images || !is_array($images)) {
            return false;
        }
        
        // Process the first 10 images
        $batch = array_slice($images, 0, 10);
        $remainingImages = array_slice($images, 10);
        
        // Import batch of images
        $this->importImageBatch($productId, $batch);
        
        // If there are remaining images, update transient and reschedule
        if (!empty($remainingImages)) {
            set_transient($transientKey, $remainingImages, DAY_IN_SECONDS);
            
            $task = new ImageImportTask();
            $task->schedule($productId);
            
            return false; // Not completed yet
        }
        
        // All images processed, delete transient
        delete_transient($transientKey);
        
        return true; // Completed
    }
    
    /**
     * Import a batch of images
     *
     * @param int $productId
     * @param array $images
     * @return void
     */
    private function importImageBatch(int $productId, array $images): void
    {
        if (empty($images)) {
            return;
        }
        
        $galleryIds = [];
        $featuredImageId = 0;
        
        foreach ($images as $index => $image) {
            $imageId = $this->importSingleImage($image['src'], $productId);
            
            if ($imageId) {
                if ($index === 0) {
                    // First image is the featured image
                    $featuredImageId = $imageId;
                    set_post_thumbnail($productId, $imageId);
                } else {
                    // Additional images go to the gallery
                    $galleryIds[] = $imageId;
                }
            }
        }
        
        // Update product gallery
        if (!empty($galleryIds)) {
            update_post_meta($productId, '_product_image_gallery', implode(',', $galleryIds));
        }
        
        // Update progress
        $this->updateImportProgress($productId, count($images));
    }
    
    /**
     * Import a single image
     *
     * @param string $imageUrl
     * @param int $productId
     * @return int|false Attachment ID or false on failure
     */
    private function importSingleImage(string $imageUrl, int $productId)
    {
        // Get the file name from URL
        $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
        
        // Check if the image has already been imported
        $existingAttachment = $this->getExistingAttachment($imageUrl);
        if ($existingAttachment) {
            return $existingAttachment;
        }
        
        // Get WordPress upload directory
        $uploadDir = wp_upload_dir();
        
        // Ensure unique filename
        $filename = wp_unique_filename($uploadDir['path'], $filename);
        $uploadFile = $uploadDir['path'] . '/' . $filename;
        
        // Download the file
        $response = wp_remote_get($imageUrl, [
            'timeout' => 60,
            'stream' => true,
            'filename' => $uploadFile
        ]);
        
        if (is_wp_error($response)) {
            error_log('Error downloading image: ' . $response->get_error_message());
            return false;
        }
        
        // Check if download was successful
        if (200 !== wp_remote_retrieve_response_code($response)) {
            error_log('Error downloading image: HTTP ' . wp_remote_retrieve_response_code($response));
            return false;
        }
        
        // Get file type
        $fileType = wp_check_filetype($filename, null);
        
        // Prepare attachment data
        $attachment = [
            'post_mime_type' => $fileType['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        
        // Insert the attachment
        $attachId = wp_insert_attachment($attachment, $uploadFile, $productId);
        
        if (is_wp_error($attachId)) {
            error_log('Error creating attachment: ' . $attachId->get_error_message());
            return false;
        }
        
        // Include image processing functions
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Generate metadata
        $attachData = wp_generate_attachment_metadata($attachId, $uploadFile);
        wp_update_attachment_metadata($attachId, $attachData);
        
        // Store the original URL for future reference
        update_post_meta($attachId, '_wpwps_source_url', $imageUrl);
        
        return $attachId;
    }
    
    /**
     * Check if an image with the given source URL already exists
     *
     * @param string $sourceUrl
     * @return int|false Attachment ID or false if not found
     */
    private function getExistingAttachment(string $sourceUrl)
    {
        global $wpdb;
        
        $attachId = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wpwps_source_url' AND meta_value = %s",
            $sourceUrl
        ));
        
        return $attachId ? (int) $attachId : false;
    }
    
    /**
     * Update import progress in transient
     *
     * @param int $productId
     * @param int $imagesImported
     * @return void
     */
    private function updateImportProgress(int $productId, int $imagesImported): void
    {
        $progressKey = 'wpwps_import_progress_' . $productId;
        $progress = get_transient($progressKey) ?: ['total' => 0, 'imported' => 0];
        
        $progress['imported'] += $imagesImported;
        
        set_transient($progressKey, $progress, HOUR_IN_SECONDS);
    }
}
