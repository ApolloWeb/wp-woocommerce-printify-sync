/**
 * ProductImportCron class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ProductImportCron
{
    public function __construct()
    {
        // Hook to schedule product import through an admin action or AJAX.
        add_action('wp_printify_sync_schedule_import', [$this, 'scheduleImport']);
        // Register the Action Scheduler callback to run the import.
        add_action('awpps_run_product_import', [$this, 'runImport']);
    }

    public function scheduleImport(): void
    {
        if (!class_exists('ActionScheduler')) {
            error_log('[Printify Import] Action Scheduler not available at ' . current_time('Y-m-d H:i:s'));
            return;
        }

        // Enqueue an asynchronous action in the "printify-import" group.
        as_enqueue_async_action('awpps_run_product_import', [], 'printify-import');
        error_log('[Printify Import] Product import scheduled via Action Scheduler at ' . current_time('Y-m-d H:i:s'));
    }

    public function runImport(): void
    {
        if (!class_exists('ProductImport') || !class_exists('PrintifyAPI')) {
            error_log('[Printify Import] Required classes do not exist for product import at ' . current_time('Y-m-d H:i:s'));
            return;
        }

        $api      = new PrintifyAPI();
        $importer = new ProductImport($api);
        $importer->runImport();

        error_log('[Printify Import] Product import completed at ' . current_time('Y-m-d H:i:s'));
    }
}

// Initialize the cron integration.
new ProductImportCron();
