<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Init {

    public static function run() {
        add_action('init', [__CLASS__, 'initHooks']);
    }

    public static function initHooks() {
        add_action('admin_menu', [__CLASS__, 'addAdminPages']);
        add_action('wp_ajax_save_settings', [__NAMESPACE__ . '\AdminSettings', 'saveSettings']);
        // Other hooks can be added here
    }

    public static function addAdminPages() {
        add_menu_page(
            __('Printify Sync Settings', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-settings',
            [__NAMESPACE__ . '\AdminSettings', 'renderSettingsPage'],
            'dashicons-admin-generic'
        );
    }
}