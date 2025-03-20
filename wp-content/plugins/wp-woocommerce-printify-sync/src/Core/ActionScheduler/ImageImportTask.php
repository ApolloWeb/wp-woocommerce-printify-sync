<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core\ActionScheduler;

use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Handlers\ProductImageHandler;

class ImageImportTask
{
    const ACTION_HOOK = 'wpwps_process_image_import';
    
    /**
     * Schedule the image import task
     *
     * @param int $productId
     * @return void
     */
    public function schedule(int $productId): void
    {
        if (!class_exists('ActionScheduler')) {
            require_once(WP_PLUGIN_DIR . '/woocommerce/includes/libraries/action-scheduler/action-scheduler.php');
        }
        
        // Schedule the task to run in 10 seconds
        as_schedule_single_action(
            time() + 10, 
            self::ACTION_HOOK, 
            ['product_id' => $productId]
        );
    }
    
    /**
     * Process the image import task
     *
     * @param int $productId
     * @return void
     */
    public function process(int $productId): void
    {
        $imageHandler = new ProductImageHandler();
        $completed = $imageHandler->processImageImport($productId);
        
        // If not completed, the handler will have rescheduled itself
        if ($completed) {
            // Update product status or trigger any post-import actions
            do_action('wpwps_product_images_imported', $productId);
        }
    }
    
    /**
     * Register the action handlers
     *
     * @return void
     */
    public static function register(): void
    {
        add_action(self::ACTION_HOOK, function($args) {
            $task = new self();
            $task->process($args['product_id']);
        });
    }
}
