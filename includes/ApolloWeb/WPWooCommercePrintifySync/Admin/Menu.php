<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class Menu {
    public function __construct() {
        add_action('admin_menu', [$this, 'addMenuItems']);
    }

    public function addMenuItems() {
        add_menu_page(
            __('Printify Sync Settings', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-settings',
            [$this, 'renderSettingsPage'],
            'dashicons-admin-generic' // This will be replaced with Font Awesome icon using CSS
        );
    }

    public function renderSettingsPage() {
        // Render the settings page
        echo '<h1>' . __('Printify Sync Settings', 'wp-woocommerce-printify-sync') . '</h1>';
    }

    public function register() {
        // Register the actions
    }
}