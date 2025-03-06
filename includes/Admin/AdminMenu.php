<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminMenu {
    public static function init() {
        add_action('admin_menu', [self::class, 'addMenu']);
    }

    public static function addMenu() {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync',
            [self::class, 'renderDashboard'],
            'dashicons-update',
            6
        );
    }

    public static function renderDashboard() {
        include WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
}