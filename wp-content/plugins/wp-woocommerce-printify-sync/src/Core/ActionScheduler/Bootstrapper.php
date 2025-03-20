<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core\ActionScheduler;

class Bootstrapper
{
    /**
     * Initialize Action Scheduler integration
     */
    public static function init(): void
    {
        // Make sure Action Scheduler is loaded
        if (!class_exists('ActionScheduler')) {
            if (file_exists(WP_PLUGIN_DIR . '/woocommerce/includes/libraries/action-scheduler/action-scheduler.php')) {
                include_once WP_PLUGIN_DIR . '/woocommerce/includes/libraries/action-scheduler/action-scheduler.php';
            } else {
                error_log('Action Scheduler library not found. Background processing disabled.');
                return;
            }
        }
        
        // Register handlers
        ProductImportTask::register();
        ImageImportTask::register();
        AllProductsImportTask::register();
        OrderImportTask::register();
        
        // Hook into plugin initialization
        add_action('wpwps_initialized', [self::class, 'registerStoreHooks']);
    }
    
    /**
     * Register store-related hooks that need to be available after plugin init
     */
    public static function registerStoreHooks(): void
    {
        // Add hooks for starting batch imports
        add_action('wpwps_start_product_import', function($products, $shopId) {
            self::startBatchImport($products, $shopId);
        }, 10, 2);
        
        // Add hook for starting import of all products
        add_action('wpwps_start_all_products_import', function($shopId) {
            self::startAllProductsImport($shopId);
        });
    }
    
    /**
     * Start a batch import process
     *
     * @param array $products
     * @param string $shopId
     */
    public static function startBatchImport(array $products, string $shopId): void
    {
        // Generate a unique key for this import
        $batchKey = 'wpwps_batch_import_' . $shopId . '_' . time();
        
        // Store products in a transient
        set_transient($batchKey, $products, DAY_IN_SECONDS);
        
        // Initialize progress tracking
        set_transient('wpwps_import_progress_' . $shopId, [
            'total' => count($products),
            'imported' => 0,
            'failed' => 0,
            'percentage' => 0,
            'timestamp' => time(),
            'last_updated' => time()
        ], HOUR_IN_SECONDS);
        
        // Schedule the first batch
        $task = new ProductImportTask();
        $task->schedule($shopId, $batchKey);
    }
    
    /**
     * Start an import of all products
     *
     * @param string $shopId
     * @return void
     */
    public static function startAllProductsImport(string $shopId): void
    {
        // Initialize progress tracking
        set_transient('wpwps_all_products_import_progress_' . $shopId, [
            'total' => 0,
            'scheduled' => 0,
            'current_page' => 0,
            'percentage' => 0,
            'timestamp' => time(),
            'last_updated' => time()
        ], HOUR_IN_SECONDS);
        
        // Schedule the task
        $task = new AllProductsImportTask();
        $task->schedule($shopId);
    }
}
