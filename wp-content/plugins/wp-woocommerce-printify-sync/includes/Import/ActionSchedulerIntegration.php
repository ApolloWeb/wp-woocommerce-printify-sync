<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Import;

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
                    </div>
                    <?php
                });
                
                return;
            }
        }
        
        // Register actions if Action Scheduler is available
        if (class_exists('ActionScheduler') || function_exists('as_enqueue_async_action')) {
            add_action('wpwps_start_product_import', [ProductImporter::class, 'startImport'], 10, 3);
            add_action('wpwps_process_product_import_queue', [ProductImporter::class, 'processImportQueue'], 10, 1);
        }
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
     * Check if Action Scheduler is available
     * 
     * @return bool
     */
    public static function isActionSchedulerAvailable(): bool
    {
        return class_exists('ActionScheduler') || function_exists('as_enqueue_async_action');
    }
}
