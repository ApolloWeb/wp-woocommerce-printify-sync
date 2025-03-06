<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ErrorLogsController {
    public static function init() {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueScripts']);
    }

    public static function addMenu() {
        add_submenu_page(
            'printify-sync',
            __('Error Logs', 'wp-woocommerce-printify-sync'),
            __('Error Logs', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-error-logs',
            [self::class, 'renderErrorLogsPage']
        );
    }

    public static function renderErrorLogsPage() {
        include WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'templates/admin-error-logs.php';
    }

    public static function enqueueScripts($hook) {
        if ($hook !== 'printify-sync_page_printify-sync-error-logs') {
            return;
        }
        wp_enqueue_style('printify-sync-error-logs-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/error-logs.css');
        wp_enqueue_script('printify-sync-error-logs-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/error-logs.js', ['jquery'], null, true);
    }
}