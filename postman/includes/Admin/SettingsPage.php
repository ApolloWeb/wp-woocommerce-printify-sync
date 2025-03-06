<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class SettingsPage {
    public static function init() {
        add_action('admin_init', [self::class, 'registerSettings']);
        add_action('admin_menu', [self::class, 'addSettingsPage']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueScripts']);
    }

    public static function registerSettings() {
        register_setting('printify_sync_settings', 'printify_api_key');
        register_setting('printify_sync_settings', 'woocommerce_api_key');
        register_setting('printify_sync_settings', 'geolocation_api_key');
        register_setting('printify_sync_settings', 'currency_api_key');
    }

    public static function addSettingsPage() {
        add_submenu_page(
            'printify-sync',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-settings',
            [self::class, 'renderSettingsPage']
        );
    }

    public static function renderSettingsPage() {
        include WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'templates/settings-page.php';
    }

    public static function enqueueScripts($hook) {
        if ($hook !== 'printify-sync_page_printify-sync-settings') {
            return;
        }
        wp_enqueue_style('printify-sync-settings-css', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/css/settings.css');
        wp_enqueue_script('printify-sync-settings-js', WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'assets/js/settings.js', ['jquery'], null, true);
    }
}