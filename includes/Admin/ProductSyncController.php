<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ProductSyncController {
    public static function init() {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueScripts']);
    }

    public static function addMenu() {
        add_submenu_page(
            'printify-sync',
            __('Product Sync', 'wp-woocommerce-printify-sync'),
            __('Product Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-product',
            [self::class, 'renderProductSyncPage']
        );
    }

    public static function renderProductSyncPage() {
        include WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'templates/admin-product-sync.php';
    }

    public static function enqueueScripts($hook) {
        if ($hook !== 'printify-sync_page_printify-sync-product') {
            return;
        }
        wp_enqueue_style('printify-sync-product-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/product-sync.css');
        wp_enqueue_script('printify-sync-product-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/product-sync.js', ['jquery'], null, true);
    }
}