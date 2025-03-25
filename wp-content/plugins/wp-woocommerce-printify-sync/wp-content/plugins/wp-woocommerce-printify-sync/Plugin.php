<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\SettingsPage;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\EncryptionHelper;

class Plugin
{
    public static function init(): void
    {
        $container = new ServiceContainer();

        add_action('plugins_loaded', [self::class, 'loaded']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminScripts']);
        add_action('wp_ajax_save_settings', [self::class, 'saveSettings']);
        add_action('admin_menu', [self::class, 'addAdminMenu']);
    }

    public static function loaded(): void
    {
        // Load services, hooks, or other initialization
    }

    public static function enqueueAdminScripts($hook_suffix): void
    {
        // Enqueue scripts and styles conditionally
        if ($hook_suffix === 'toplevel_page_wp-woocommerce-printify-sync-settings') {
            wp_enqueue_style('wpwps-settings', plugins_url('assets/css/wpwps-settings.css', __FILE__));
            wp_enqueue_script('wpwps-settings', plugins_url('assets/js/wpwps-settings.js', __FILE__), ['jquery'], null, true);
        }
    }

    public static function saveSettings(): void
    {
        // Verify nonce and user capabilities
        if (!current_user_can('manage_options') || !check_ajax_referer('wpwps_save_settings', 'nonce', false)) {
            wp_send_json_error('Unauthorized request.');
            return;
        }

        // Get settings from request
        $settings = $_POST['settings'] ?? [];

        // Save settings securely
        update_option('wpwps_printify_api_key', EncryptionHelper::encrypt($settings['printify_api_key']));
        update_option('wpwps_api_endpoint', sanitize_text_field($settings['api_endpoint']));
        update_option('wpwps_shop_id', sanitize_text_field($settings['shop_id']));
        update_option('wpwps_chatgpt_api_key', EncryptionHelper::encrypt($settings['chatgpt_api_key']));
        update_option('wpwps_monthly_spend_cap', intval($settings['monthly_spend_cap']));
        update_option('wpwps_number_of_tokens', intval($settings['number_of_tokens']));
        update_option('wpwps_temperature', floatval($settings['temperature']));

        wp_send_json_success('Settings saved successfully.');
    }

    public static function addAdminMenu(): void
    {
        add_menu_page(
            'WP WooCommerce Printify Sync',
            'Printify Sync',
            'manage_options',
            'wp-woocommerce-printify-sync',
            [DashboardPage::class, 'render'],
            'dashicons-tshirt'
        );

        add_submenu_page(
            'wp-woocommerce-printify-sync',
            'Settings',
            'Settings',
            'manage_options',
            'wp-woocommerce-printify-sync-settings',
            [SettingsPage::class, 'render']
        );
    }
}
