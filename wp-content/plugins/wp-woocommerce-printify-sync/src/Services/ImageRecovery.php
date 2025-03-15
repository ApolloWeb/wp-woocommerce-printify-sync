<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImageRecovery
{
    private string $currentTime = '2025-03-15 20:00:16';
    private string $currentUser = 'ApolloWeb';
    private ImageHandler $imageHandler;

    public function __construct()
    {
        $this->imageHandler = new ImageHandler();
        add_action('wpwps_retry_failed_images', [$this, 'retryFailedImages']);
    }

    public function retryFailedImages(): void
    {
        global $wpdb;

        // Get failed image imports
        $failedImages = $wpdb->get_results("
            SELECT post_id, meta_value as url 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_failed_image'
            LIMIT 10
        ");

        foreach ($failedImages as $image) {
            try {
                $imageId = $this->imageHandler->handleImage($image->url, $image->post_id);
                delete_post_meta($image->post_id, '_printify_failed_image');
                
                // Log success
                update_post_meta(
                    $image->post_id,
                    '_printify_image_recovery',
                    sprintf('Recovered on %s by %s', $this->currentTime, $this->currentUser)
                );

            } catch (\Exception $e) {
                // Update retry count
                $retries = (int) get_post_meta($image->post_id, '_printify_image_retries', true) + 1;
                update_post_meta($image->post_id, '_printify_image_retries', $retries);

                if ($retries >= 3) {
                    // Mark as permanently failed after 3 retries
                    update_post_meta($image->post_id, '_printify_image_permanent_failure', $e->getMessage());
                }
            }
        }
    }
}