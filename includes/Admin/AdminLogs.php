<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminLogs {
    public function __construct() {
        add_action('admin_menu', [$this, 'addLogsPage']);
    }

    public function addLogsPage() {
        add_submenu_page(
            'printify-sync-settings',
            __('Printify Sync Logs', 'wp-woocommerce-printify-sync'),
            __('Logs', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-logs',
            [$this, 'renderLogsPage']
        );
    }

    public function renderLogsPage() {
        // Render the logs page
        echo '<h1>' . __('Printify Sync Logs', 'wp-woocommerce-printify-sync') . '</h1>';
    }
}