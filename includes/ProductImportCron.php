<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class ProductImportCron {
    public static function init() {
        add_action( 'wp_woocommerce_printify_sync_import_chunk', [ __CLASS__, 'import_chunk' ] );
        add_action( 'wp_woocommerce_printify_sync_cron', [ __CLASS__, 'import_products' ] );
    }

    public static function schedule() {
        if ( ! wp_next_scheduled( 'wp_woocommerce_printify_sync_cron' ) ) {
            wp_schedule_event( time(), 'hourly', 'wp_woocommerce_printify_sync_cron' );
        }
    }

    public static function unschedule() {
        wp_clear_scheduled_hook( 'wp_woocommerce_printify_sync_cron' );
    }

    public static function import_products() {
        $importer = new ProductImport();
        $importer->import_products();
    }

    public static function import_chunk( $chunk ) {
        $importer = new ProductImport();
        $importer->import_chunk( $chunk );
    }
}