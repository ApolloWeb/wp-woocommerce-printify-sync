<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class ProductImporter {

    public static function importProducts() {
        // Logic to import products from Printify to WooCommerce
    }

    public static function scheduleImport() {
        if (!wp_next_scheduled('printify_sync_cron_job')) {
            wp_schedule_event(time(), 'hourly', 'printify_sync_cron_job');
        }
    }

    public static function handleImportCronJob() {
        self::importProducts();
        // Optimize images using SMUSH
        if (class_exists('WP_Smush')) {
            WP_Smush::get_instance()->core()->auto_smushit();
        }
    }
}

add_action('wp', ['ApolloWeb\WPWooCommercePrintifySync\ProductImporter', 'scheduleImport']);
add_action('printify_sync_cron_job', ['ApolloWeb\WPWooCommercePrintifySync\ProductImporter', 'handleImportCronJob']);