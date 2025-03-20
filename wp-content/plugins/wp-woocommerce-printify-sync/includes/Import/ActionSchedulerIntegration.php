<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Import;

/**
 * Action Scheduler Integration
 */
class ActionSchedulerIntegration
{
    /**
     * Initialize Action Scheduler
     */
    public static function init(): void
    {
        // Check if Action Scheduler is already loaded
        if (!class_exists('ActionScheduler') && !function_exists('as_enqueue_async_action')) {
            // Try multiple possible locations for Action Scheduler
            $possible_paths = [
                // WooCommerce 3.5+
                WP_PLUGIN_DIR . '/woocommerce/includes/libraries/action-scheduler/action-scheduler.php',
                // WooCommerce 4.0+
                WP_PLUGIN_DIR . '/woocommerce/packages/action-scheduler/action-scheduler.php',
                // Standalone Action Scheduler plugin
                WP_PLUGIN_DIR . '/action-scheduler/action-scheduler.php',
                // Look in the vendor directory as well
                WP_PLUGIN_DIR . '/wp-woocommerce-printify-sync/vendor/woocommerce/action-scheduler/action-scheduler.php',
            ];
            
            $action_scheduler_loaded = false;
            
            foreach ($possible_paths as $path) {
                if (file_exists($path)) {
                    require_once($path);
                    $action_scheduler_loaded = true;
                    break;
                }
            }
            
            // If we couldn't find Action Scheduler, log an error but don't crash
            if (!$action_scheduler_loaded) {
                error_log('WP WooCommerce Printify Sync: Action Scheduler library not found. Product import functionality will be limited.');
                
                // Add admin notice
                add_action('admin_notices', function() {
                    if (!current_user_can('manage_options')) return;
                    ?>
                    <div class="notice notice-error">
                        <p>
                            <strong>WP WooCommerce Printify Sync:</strong> 
                            <?php _e('Action Scheduler library not found. Please ensure WooCommerce is installed and activated. Product import functionality will be limited.', 'wp-woocommerce-printify-sync'); ?>
                        </p>
                        <p>
                            <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="button button-primary">
                                <?php _e('Check Plugins', 'wp-woocommerce-printify-sync'); ?>
                            </a>
                            <a href="https://actionscheduler.org/" target="_blank" class="button button-secondary">
                                <?php _e('Learn More About Action Scheduler', 'wp-woocommerce-printify-sync'); ?>
                            </a>
                        </p>
                    </div>
                    <?php
                });
                
                return;
            }
        }
        
        // Register actions if Action Scheduler is available
        if (class_exists('ActionScheduler') || function_exists('as_enqueue_async_action')) {
            // Make sure we don't register the same hooks multiple times
            if (!has_action('wpwps_start_product_import', [ProductImporter::class, 'startImport'])) {
                add_action('wpwps_start_product_import', [ProductImporter::class, 'startImport'], 10, 3);
            }
            
            if (!has_action('wpwps_process_product_import_queue', [ProductImporter::class, 'processImportQueue'])) {
                add_action('wpwps_process_product_import_queue', [ProductImporter::class, 'processImportQueue'], 10, 1);
            }
        }
    }
    
    /**
     * Schedule a product import
     * 
     * @param array $products The products to import
     * @param string $syncMode The sync mode
     * @return bool Whether the import was scheduled
     */
    public static function scheduleImport(array $products, string $syncMode = 'all'): bool
    {
        if (!self::isActionSchedulerAvailable()) {
            return false;
        }
        
        // Cancel any existing import jobs before starting a new one
        self::cancelImport();
        
        // Reset import stats
        update_option('wpwps_import_stats', [
            'total' => count($products),
            'processed' => 0,
            'imported' => 0,
            'updated' => 0,
            'failed' => 0,
        ]);
        
        // Start the import with a 5-second delay to ensure the UI has time to update
        as_schedule_single_action(time() + 5, 'wpwps_start_product_import', [
            'products' => $products,
            'sync_mode' => $syncMode,
            'batch_size' => 5, // Process 5 products at a time
        ]);
        
        update_option('wpwps_last_import_timestamp', time());
        
        return true;
    }
    
    /**
     * Get import status information
     * 
     * @return array
     */
    public static function getImportStatus(): array
    {
        $lastImport = get_option('wpwps_last_import_timestamp', 0);
        $lastCompleted = get_option('wpwps_last_import_completed', 0);
        
        // Only check for scheduled actions if Action Scheduler is available
        $importRunning = false;
        if (function_exists('as_has_scheduled_action')) {
            $importRunning = as_has_scheduled_action('wpwps_process_product_import_queue');
        }
        
        $importStats = get_option('wpwps_import_stats', [
            'total' => 0,
            'processed' => 0,
            'imported' => 0,
            'updated' => 0,
            'failed' => 0,
        ]);
        
        // Calculate progress percentage
        $progress = 0;
        if ($importStats['total'] > 0) {
            $progress = round(($importStats['processed'] / $importStats['total']) * 100);
        }
        
        return [
            'last_import' => $lastImport,
            'last_completed' => $lastCompleted,
            'is_running' => $importRunning,
            'stats' => $importStats,
            'progress' => $progress,
        ];
    }
    
    /**
     * Cancel all scheduled import actions
     */
    public static function cancelImport(): void
    {
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('wpwps_process_product_import_queue');
            as_unschedule_all_actions('wpwps_start_product_import');
        }
    }
    
    /**
     * Check if Action Scheduler is available and properly initialized
     * 
     * @return bool
     */
    public static function isActionSchedulerAvailable(): bool
    {
        $available = class_exists('ActionScheduler') || function_exists('as_enqueue_async_action');
        
        if ($available) {
            // Verify that Action Scheduler is properly set up
            if (!function_exists('as_next_scheduled_action') || !function_exists('as_schedule_single_action')) {
                error_log('WP WooCommerce Printify Sync: Action Scheduler functions not available');
                return false;
            }
        }
        
        return $available;
    }
    
    /**
     * Get the Action Scheduler admin URL
     * 
     * @return string
     */
    public static function getActionSchedulerAdminUrl(): string
    {
        return admin_url('admin.php?page=wc-status&tab=action-scheduler&status=pending&s=wpwps');
    }

    /**
     * Mark a product as failed in the sync process
     * 
     * @param string $printifyProductId Printify product ID
     * @param string $errorMessage Error message
     * @return void 
     */
    public static function markProductSyncFailed(string $printifyProductId, string $errorMessage): void
    {
        $wcProductId = ProductMetaHelper::findProductByPrintifyId($printifyProductId);
        if ($wcProductId) {
            ProductMetaHelper::updateSyncStatus($wcProductId, 'failed');
            update_post_meta($wcProductId, '_printify_sync_error', $errorMessage);
        }
        
        // Log the error
        error_log("Printify Sync Error for product {$printifyProductId}: {$errorMessage}");
        
        // Update the import stats
        $importStats = get_option('wpwps_import_stats', [
            'total' => 0,
            'processed' => 0,
            'imported' => 0,
            'updated' => 0,
            'failed' => 0,
        ]);
        
        $importStats['failed']++;
        $importStats['processed']++;
        
        update_option('wpwps_import_stats', $importStats);
    }
}
