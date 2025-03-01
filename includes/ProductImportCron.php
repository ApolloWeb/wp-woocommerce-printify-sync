<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class ProductImportCron {
    public static function init() {
        add_action( 'wp_woocommerce_printify_sync_import_chunk', [ __CLASS__, 'import_chunk' ] );
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