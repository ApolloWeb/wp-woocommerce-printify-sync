<?php
/**
 * Settings page.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Pages
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\TemplateRenderer;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Settings admin page.
 */
class Settings {
    /**
     * PrintifyAPI instance.
     *
     * @var PrintifyAPI
     */
    private $api;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * TemplateRenderer instance.
     *
     * @var TemplateRenderer
     */
    private $template;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->logger = new Logger();
        $this->api = new PrintifyAPI($this->logger);
        $this->template = new TemplateRenderer();
    }

    /**
     * Initialize Settings page.
     *
     * @return void
     */
    public function init() {
        add_action('wp_ajax_wpwps_test_printify_connection', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_wpwps_test_gpt_connection', [$this, 'testGptConnection']);
        add_action('wp_ajax_wpwps_save_settings', [$this, 'saveSettings']);
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render() {
        $settings = get_option('wpwps_settings', []);
        $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
        $api_endpoint = isset($settings['api_endpoint']) ? $settings['api_endpoint'] : 'https://api.printify.com/v1/';
        $shop_id = isset($settings['shop_id']) ? $settings['shop_id'] : '';
        $shop_name = isset($settings['shop_name']) ? $settings['shop_name'] : '';
        $gpt_api_key = isset($settings['gpt_api_key']) ? $settings['gpt_api_key'] : '';
        $gpt_tokens = isset($settings['gpt_tokens']) ? $settings['gpt_tokens'] : 2000;
        $gpt_temperature = isset($settings['gpt_temperature']) ? $settings['gpt_temperature'] : 0.7;
        $gpt_budget = isset($settings['gpt_budget']) ? $settings['gpt_budget'] : 50;
        $sync_external_order_id = isset($settings['sync_external_order_id']) ? $settings['sync_external_order_id'] : false;

        // Render template
        $this->template->render('settings', [
            'api_key' => $api_key,
            'api_endpoint' => $api_endpoint,
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
            'gpt_api_key' => $gpt_api_key,
            'gpt_tokens' => $gpt_tokens,
            'gpt_temperature' => $gpt_temperature,
            'gpt_budget' => $gpt_budget,
            'sync_external_order_id' => $sync_external_order_id,
            'settings' => $settings,
            'nonce' => wp_create_nonce('wpwps_settings_nonce'),
        ]);
    }

    /**
     * Test Printify connection.
     *
     * @return void
     */
    public function testPrintifyConnection() {
        // Check nonce
        check_ajax_referer('wpwps_settings_nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        // Get API key
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : 'https://api.printify.com/v1/';
        
        if (empty($api_key)) {
            wp_send_json_error([
                'message' => __('API key is required.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        // Create temporary API instance with the new key
        $temp_api = new PrintifyAPI($this->logger);
        
        // Temporarily update settings for test
        $settings = get_option('wpwps_settings', []);
        $settings['api_key'] = $temp_api->encryptApiKey($api_key);
        $settings['api_endpoint'] = $api_endpoint;
        update_option('wpwps_settings', $settings);
        
        // Test connection
        $result = $temp_api->testConnection();
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }
        
        // Get shops
        $shops = $temp_api->getShops();
        
        if (is_wp_error($shops)) {
            wp_send_json_error([
                'message' => $shops->get_error_message(),
            ]);
        }
        
        // Send shops list
        wp_send_json_success([
            'message' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
            'shops' => $shops,
        ]);
    }

    /**
     * Test GPT connection.
     *
     * @return void
     */
    public function testGptConnection() {
        // Check nonce
        check_ajax_referer('wpwps_settings_nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        // Get GPT API key
        $gpt_api_key = isset($_POST['gpt_api_key']) ? sanitize_text_field($_POST['gpt_api_key']) : '';
        $gpt_tokens = isset($_POST['gpt_tokens']) ? intval($_POST['gpt_tokens']) : 2000;
        $gpt_temperature = isset($_POST['gpt_temperature']) ? floatval($_POST['gpt_temperature']) : 0.7;
        $gpt_budget = isset($_POST['gpt_budget']) ? floatval($_POST['gpt_budget']) : 50;
        
        if (empty($gpt_api_key)) {
            wp_send_json_error([
                'message' => __('GPT API key is required.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        // Test OpenAI API connection
        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $gpt_api_key,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 30,
                'body'    => wp_json_encode([
                    'model'       => 'gpt-3.5-turbo',
                    'messages'    => [
                        [
                            'role'    => 'user',
                            'content' => 'Test connection. Reply with "Connection successful!"',
                        ],
                    ],
                    'temperature' => $gpt_temperature,
                    'max_tokens'  => 50,
                ]),
            ]
        );
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message(),
            ]);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body) || isset($body['error'])) {
            wp_send_json_error([
                'message' => isset($body['error']['message']) ? $body['error']['message'] : __('Unknown error.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        // Calculate estimated monthly cost
        $cost_per_token = 0.000002; // GPT-3.5 Turbo cost per token
        $estimated_requests_per_day = 100; // Average number of requests per day
        $estimated_tokens_per_request = $gpt_tokens;
        $estimated_cost_per_day = $estimated_requests_per_day * $estimated_tokens_per_request * $cost_per_token;
        $estimated_cost_per_month = $estimated_cost_per_day * 30;
        
        wp_send_json_success([
            'message' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
            'estimated_cost' => [
                'per_day' => round($estimated_cost_per_day, 2),
                'per_month' => round($estimated_cost_per_month, 2),
            ],
        ]);
    }

    /**
     * Save settings.
     *
     * @return void
     */
    public function saveSettings() {
        // Check nonce
        check_ajax_referer('wpwps_settings_nonce', 'nonce');
        
        // Check user capability
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        // Get settings
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : 'https://api.printify.com/v1/';
        $shop_id = isset($_POST['shop_id']) ? sanitize_text_field($_POST['shop_id']) : '';
        $shop_name = isset($_POST['shop_name']) ? sanitize_text_field($_POST['shop_name']) : '';
        $gpt_api_key = isset($_POST['gpt_api_key']) ? sanitize_text_field($_POST['gpt_api_key']) : '';
        $gpt_tokens = isset($_POST['gpt_tokens']) ? intval($_POST['gpt_tokens']) : 2000;
        $gpt_temperature = isset($_POST['gpt_temperature']) ? floatval($_POST['gpt_temperature']) : 0.7;
        $gpt_budget = isset($_POST['gpt_budget']) ? floatval($_POST['gpt_budget']) : 50;
        $sync_external_order_id = isset($_POST['sync_external_order_id']) ? true : false;
        
        // Validate settings
        if (empty($api_key)) {
            wp_send_json_error([
                'message' => __('API key is required.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        if (empty($shop_id)) {
            wp_send_json_error([
                'message' => __('Shop ID is required.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        // Save settings
        $settings = [
            'api_key' => $this->api->encryptApiKey($api_key),
            'api_endpoint' => $api_endpoint,
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
            'gpt_api_key' => $gpt_api_key,
            'gpt_tokens' => $gpt_tokens,
            'gpt_temperature' => $gpt_temperature,
            'gpt_budget' => $gpt_budget,
            'sync_external_order_id' => $sync_external_order_id,
        ];
        
        update_option('wpwps_settings', $settings);
        
        wp_send_json_success([
            'message' => __('Settings saved successfully.', 'wp-woocommerce-printify-sync'),
        ]);
    }
}
