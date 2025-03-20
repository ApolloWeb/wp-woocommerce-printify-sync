<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Import;

class ActionSchedulerIntegration
{
    /**
     * Initialize Action Scheduler
     */
    public static function init(): void
    {
        // Make sure Action Scheduler is loaded
        if (!function_exists('as_enqueue_async_action')) {
            require_once(WP_PLUGIN_DIR . '/woocommerce/includes/libraries/action-scheduler/action-scheduler.php');
        }
        
        // Register actions
        add_action('wpwps_start_product_import', [ProductImporter::class, 'startImport'], 10, 3);
        add_action('wpwps_process_product_import_queue', [ProductImporter::class, 'processImportQueue'], 10, 1);
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
        $importRunning = as_has_scheduled_action('wpwps_process_product_import_queue');
        
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
        as_unschedule_all_actions('wpwps_process_product_import_queue');
        as_unschedule_all_actions('wpwps_start_product_import');
    }
}
