<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminDashboard {
    public static function init() {
        add_action('admin_menu', [self::class, 'addDashboard']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueScripts']);
    }

    public static function addDashboard() {
        add_submenu_page(
            'printify-sync',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-dashboard',
            [self::class, 'renderDashboard']
        );
    }

    public static function renderDashboard() {
        include WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    public static function enqueueScripts($hook) {
        if ($hook !== 'printify-sync_page_printify-sync-dashboard') {
            return;
        }
        wp_enqueue_style('printify-sync-dashboard-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/dashboard.css');
        wp_enqueue_script('printify-sync-dashboard-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/dashboard.js', ['jquery'], null, true);
    }
}