<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Services\BladeTemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Services\ChatGPTAPI;
use ApolloWeb\WPWooCommercePrintifySync\Services\Settings as SettingsService;

class Settings {
    private $template;
    private $printifyAPI;
    private $chatGPTAPI;
    private $settings;

    public function __construct(BladeTemplateEngine $template, PrintifyAPI $printifyAPI) {
        $this->template = $template;
        $this->printifyAPI = $printifyAPI;
        $this->chatGPTAPI = new ChatGPTAPI();
        $this->settings = new SettingsService();

        // Register AJAX handlers
        add_action('wp_ajax_wpwps_test_printify', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_wpwps_save_printify_settings', [$this, 'savePrintifySettings']);
        add_action('wp_ajax_wpwps_save_chatgpt_settings', [$this, 'saveChatGPTSettings']);
        add_action('wp_ajax_wpwps_estimate_chatgpt', [$this, 'estimateChatGPTCost']);

        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void {
        // Get current settings
        $printifyApiKey = $this->settings->getPrintifyApiKey();
        $printifyApiEndpoint = $this->settings->getPrintifyApiEndpoint();
        $printifyShopId = $this->settings->getPrintifyShopId();
        
        // If we have a shop ID, fetch the shop details
        $shopDetails = null;
        if ($printifyShopId && $printifyApiKey) {
            try {
                $shops = $this->printifyAPI->getShops();
                foreach ($shops as $shop) {
                    if ($shop['id'] === $printifyShopId) {
                        $shopDetails = $shop;
                        break;
                    }
                }
            } catch (\Exception $e) {
                // Log error but don't display to user
                error_log('Failed to fetch shop details: ' . $e->getMessage());
            }
        }

        echo $this->template->render('wpwps-settings', [
            'printify_api_key' => $printifyApiKey,
            'printify_api_endpoint' => $printifyApiEndpoint,
            'printify_shop_id' => $printifyShopId,
            'shop_details' => $shopDetails,
            'chatgpt_api_key' => $this->settings->getChatGPTApiKey(),
            'chatgpt_settings' => $this->settings->getChatGPTSettings(),
        ]);
    }

    public function enqueueAssets(string $hook): void {
        if ($hook !== 'printify-sync_page_wpwps-settings') {
            return;
        }

        // Enqueue shared assets
        wp_enqueue_style('google-fonts-inter');
        wp_enqueue_style('bootstrap');
        wp_enqueue_script('bootstrap');
        wp_enqueue_style('font-awesome');
        wp_enqueue_script('wpwps-toast');

        // Our custom page assets
        wp_enqueue_style(
            'wpwps-settings',
            WPWPS_URL . 'assets/css/wpwps-settings.css',
            [],
            WPWPS_VERSION
        );
        wp_enqueue_script(
            'wpwps-settings',
            WPWPS_URL . 'assets/js/wpwps-settings.js',
            ['jquery', 'bootstrap', 'wpwps-toast'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-settings', 'wpwps_settings', [
            'nonce' => wp_create_nonce('wpwps_settings_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'user' => [
                'display_name' => wp_get_current_user()->display_name,
                'avatar' => get_avatar_url(get_current_user_id()),
                'role' => array_values(wp_get_current_user()->roles)[0]
            ]
        ]);
    }

    public function testPrintifyConnection(): void {
        check_ajax_referer('wpwps_settings_nonce', 'nonce');

        $api_key = sanitize_text_field($_POST['api_key']);
        $api_endpoint = esc_url_raw($_POST['api_endpoint']);

        try {
            // First validate the endpoint format
            $this->printifyAPI->validateEndpoint($api_endpoint);

            // Then test the connection and get data
            $data = $this->printifyAPI->testConnection($api_key, $api_endpoint);
            
            $response = [
                'shops' => $data['shops'],
                'profile' => [
                    'name' => $data['profile']['name'] ?? '',
                    'email' => $data['profile']['email'] ?? '',
                ]
            ];

            wp_send_json_success($response);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    public function savePrintifySettings(): void {
        check_ajax_referer('wpwps_settings_nonce', 'nonce');

        $api_key = sanitize_text_field($_POST['api_key']);
        $api_endpoint = esc_url_raw($_POST['api_endpoint']);
        $shop_id = sanitize_text_field($_POST['shop_id'] ?? '');

        try {
            // Validate endpoint format
            $this->printifyAPI->validateEndpoint($api_endpoint);
            
            // Validate API key
            $this->printifyAPI->validateApiKey($api_key, $api_endpoint);

            // Save settings
            $this->settings->setPrintifyApiKey($api_key);
            $this->settings->setPrintifyApiEndpoint($api_endpoint);

            if (!empty($shop_id)) {
                $this->settings->setPrintifyShopId($shop_id);
                
                // Get shop details for confirmation
                $shops = $this->printifyAPI->getShops();
                $selectedShop = null;
                foreach ($shops as $shop) {
                    if ($shop['id'] === $shop_id) {
                        $selectedShop = $shop;
                        break;
                    }
                }

                wp_send_json_success([
                    'shop_id' => $shop_id,
                    'shop_details' => $selectedShop
                ]);
            } else {
                wp_send_json_success();
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    public function saveChatGPTSettings(): void {
        check_ajax_referer('wpwps_settings_nonce', 'nonce');

        $api_key = sanitize_text_field($_POST['api_key']);
        $settings = [
            'monthly_cap' => (int) $_POST['monthly_cap'],
            'tokens' => (int) $_POST['tokens'],
            'temperature' => (float) $_POST['temperature'],
        ];

        try {
            $this->settings->setChatGPTApiKey($api_key);
            $this->settings->setChatGPTSettings($settings);
            wp_send_json_success();
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function estimateChatGPTCost(): void {
        check_ajax_referer('wpwps_settings_nonce', 'nonce');

        $monthly_cap = (int) $_POST['monthly_cap'];
        $tokens = (int) $_POST['tokens'];

        // GPT-3.5 Turbo pricing: $0.002 per 1K tokens
        $cost_per_1k_tokens = 0.002;
        $estimated_requests = floor($monthly_cap / ($tokens * $cost_per_1k_tokens / 1000));

        $message = sprintf(
            __('With a monthly cap of $%d and %d tokens per request, you can make approximately %d requests per month.', 'wp-woocommerce-printify-sync'),
            $monthly_cap,
            $tokens,
            $estimated_requests
        );

        wp_send_json_success(['message' => $message]);
    }
}