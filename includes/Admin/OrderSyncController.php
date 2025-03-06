<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class OrderSyncController {
    public static function init() {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueScripts']);
    }

    public static function addMenu() {
        add_submenu_page(
            'printify-sync',
            __('Order Sync', 'wp-woocommerce-printify-sync'),
            __('Order Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-order',
            [self::class, 'renderOrderSyncPage']
        );
    }

    public static function renderOrderSyncPage() {
        include WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'templates/admin-order-sync.php';
    }

    public static function enqueueScripts($hook) {
        if ($hook !== 'printify-sync_page_printify-sync-order') {
            return;
        }
        wp_enqueue_style('printify-sync-order-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/order-sync.css');
        wp_enqueue_script('printify-sync-order-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/order-sync.js', ['jquery'], null, true);
    }
}