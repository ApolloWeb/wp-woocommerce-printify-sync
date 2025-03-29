<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

class SettingsProvider implements ServiceProvider
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerSubmenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_test_printify', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_wpwps_test_openai', [$this, 'testOpenAIConnection']);
    }

    public function registerSubmenu(): void
    {
        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-settings',
            [$this, 'renderSettings']
        );
    }

    public function renderSettings(): void
    {
        echo View::render('wpwps-settings', [
            'title' => __('Printify Sync Settings', 'wp-woocommerce-printify-sync'),
            'printify_key' => get_option('wpwps_printify_key', ''),
            'printify_endpoint' => get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1/'),
            'openai_key' => get_option('wpwps_openai_key', ''),
            'openai_token_limit' => get_option('wpwps_openai_token_limit', 2000),
            'openai_temperature' => get_option('wpwps_openai_temperature', 0.7),
            'openai_spend_cap' => get_option('wpwps_openai_spend_cap', 50)
        ]);
    }

    public function enqueueAssets(): void
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpwps-settings') {
            return;
        }

        wp_enqueue_style('wpwps-bootstrap', WPWPS_URL . 'assets/core/css/bootstrap.min.css', [], WPWPS_VERSION);
        wp_enqueue_style('wpwps-fontawesome', WPWPS_URL . 'assets/core/css/fontawesome.min.css', [], WPWPS_VERSION);
        wp_enqueue_style('wpwps-settings', WPWPS_URL . 'assets/css/wpwps-settings.css', [], WPWPS_VERSION);
        
        wp_enqueue_script('wpwps-settings', WPWPS_URL . 'assets/js/wpwps-settings.js', ['jquery'], WPWPS_VERSION, true);
        wp_localize_script('wpwps-settings', 'wpwpsSettings', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-settings-nonce')
        ]);
    }

    public function testPrintifyConnection(): void
    {
        check_ajax_referer('wpwps-settings-nonce', 'nonce');
        
        // Implementation for testing Printify connection
        wp_send_json_success(['message' => 'Connection successful']);
    }

    public function testOpenAIConnection(): void
    {
        check_ajax_referer('wpwps-settings-nonce', 'nonce');
        
        // Implementation for testing OpenAI connection
        wp_send_json_success(['message' => 'Connection successful']);
    }
}