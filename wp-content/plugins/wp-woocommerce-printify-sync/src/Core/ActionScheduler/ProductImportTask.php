<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core\ActionScheduler;

use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\ProductImporter;

class ProductImportTask
{
    const ACTION_HOOK = 'wpwps_process_product_import';
    const BATCH_SIZE = 10; // Import 10 products at a time
    
    /**
     * Schedule the batch product import task
     *
     * @param string $shopId
     * @param string $batchKey Transient key containing products to import
     * @return void
     */
    public function schedule(string $shopId, string $batchKey): void
    {
        if (!class_exists('ActionScheduler')) {
            require_once(WP_PLUGIN_DIR . '/woocommerce/includes/libraries/action-scheduler/action-scheduler.php');
        }
        
        // Schedule the task to run in 10 seconds
        as_schedule_single_action(
            time() + 10, 
            self::ACTION_HOOK, 
            [
                'shop_id' => $shopId,
                'batch_key' => $batchKey
            ]
        );
    }
    
    /**
     * Process a batch of products
     *
     * @param string $shopId
     * @param string $batchKey
     * @return void
     */
    public function process(string $shopId, string $batchKey): void
    {
        // Get products from transient
        $products = get_transient($batchKey);
        
        if (!$products || !is_array($products)) {
            $this->completeImport($shopId, $batchKey);
            return;
        }
        
        // Take a batch of products
        $batch = array_slice($products, 0, self::BATCH_SIZE);
        $remainingProducts = array_slice($products, self::BATCH_SIZE);
        
        // Update progress
        $this->updateProgress($shopId, $batchKey, count($batch), count($remainingProducts));
        
        // Import the batch
        $importer = new ProductImporter();
        $imported = 0;
        $failed = 0;
        
        foreach ($batch as $product) {
            try {
                $importer->importProduct($product);
                $imported++;
            } catch (\Exception $e) {
                error_log('Error importing product: ' . $e->getMessage());
                $failed++;
            }
        }
        
        // If there are remaining products, update the transient and reschedule
        if (!empty($remainingProducts)) {
            set_transient($batchKey, $remainingProducts, DAY_IN_SECONDS);
            
            // Reschedule the task
            $this->schedule($shopId, $batchKey);
        } else {
            // All products have been processed
            $this->completeImport($shopId, $batchKey);
        }
    }
    
    /**
     * Mark the import as complete
     *
     * @param string $shopId
     * @param string $batchKey
     */
    private function completeImport(string $shopId, string $batchKey): void
    {
        // Remove transients
        delete_transient($batchKey);
        delete_transient('wpwps_import_progress_' . $shopId);
        
        // Add a flag indicating completion
        set_transient('wpwps_import_completed_' . $shopId, [
            'timestamp' => time(),
            'message' => 'Import completed successfully'
        ], HOUR_IN_SECONDS);
        
        // Trigger action for other plugins to hook into
        do_action('wpwps_product_import_completed', $shopId);
    }
    
    /**
     * Update import progress
     *
     * @param string $shopId
     * @param string $batchKey
     * @param int $imported
     * @param int $remaining
     */
    private function updateProgress(string $shopId, string $batchKey, int $imported, int $remaining): void
    {
        $progressKey = 'wpwps_import_progress_' . $shopId;
        $progress = get_transient($progressKey) ?: [
            'total' => $imported + $remaining,
            'imported' => 0,
            'failed' => 0,
            'timestamp' => time()
        ];
        
        $progress['imported'] += $imported;
        $progress['percentage'] = round(($progress['imported'] / $progress['total']) * 100);
        $progress['last_updated'] = time();
        
        set_transient($progressKey, $progress, HOUR_IN_SECONDS);
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
            $task->process($args['shop_id'], $args['batch_key']);
        });
    }
}
