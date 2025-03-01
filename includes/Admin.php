<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_pages']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_admin_pages() {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync',
            [$this, 'create_admin_page'],
            'dashicons-admin-generic',
            110
        );
    }

    public function create_admin_page() {
        include plugin_dir_path(__FILE__) . 'templates/settings-page.php';
    }

    public function register_settings() {
        register_setting('printify-sync', 'printify_api_key');
    }
}