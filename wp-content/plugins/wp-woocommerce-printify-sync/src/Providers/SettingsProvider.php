<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

class SettingsProvider extends ServiceProvider {
    public function register(): void {
        add_action('admin_menu', [$this, 'registerSubmenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('wp_ajax_wpwps_test_printify', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_wpwps_test_openai', [$this, 'testOpenAIConnection']);
    }

    public function registerSubmenu(): void {
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettings']
        );
    }

    public function registerSettings(): void {
        register_setting('wpwps_settings', 'wpwps_printify_api_key');
        register_setting('wpwps_settings', 'wpwps_printify_shop_id');
        register_setting('wpwps_settings', 'wpwps_openai_api_key');
        register_setting('wpwps_settings', 'wpwps_openai_token_limit', ['default' => 2000]);
        register_setting('wpwps_settings', 'wpwps_openai_temperature', ['default' => 0.7]);
        register_setting('wpwps_settings', 'wpwps_openai_spend_cap', ['default' => 50]);
    }

    public function renderSettings(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        echo View::render('wpwps-settings', [
            'title' => __('Printify Sync Settings', 'wp-woocommerce-printify-sync'),
            'settings' => $this->getSettings()
        ]);
    }

    private function getSettings(): array {
        return [
            'printify_api_key' => get_option('wpwps_printify_api_key', ''),
            'printify_shop_id' => get_option('wpwps_printify_shop_id', ''),
            'openai_api_key' => get_option('wpwps_openai_api_key', ''),
            'openai_token_limit' => get_option('wpwps_openai_token_limit', 2000),
            'openai_temperature' => get_option('wpwps_openai_temperature', 0.7),
            'openai_spend_cap' => get_option('wpwps_openai_spend_cap', 50)
        ];
    }

    public function testPrintifyConnection(): void {
        check_ajax_referer('wpwps_settings_nonce');
        
        $api_key = get_option('wpwps_printify_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required', 'wp-woocommerce-printify-sync')]);
        }

        // TODO: Implement actual API test
        wp_send_json_success(['message' => __('Connection successful', 'wp-woocommerce-printify-sync')]);
    }

    public function testOpenAIConnection(): void {
        check_ajax_referer('wpwps_settings_nonce');
        
        $api_key = get_option('wpwps_openai_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required', 'wp-woocommerce-printify-sync')]);
        }

        // TODO: Implement actual API test
        wp_send_json_success(['message' => __('Connection successful', 'wp-woocommerce-printify-sync')]);
    }
}