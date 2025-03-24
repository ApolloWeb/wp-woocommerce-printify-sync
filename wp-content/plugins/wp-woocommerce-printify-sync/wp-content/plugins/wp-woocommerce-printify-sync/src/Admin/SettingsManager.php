<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApi;
use ApolloWeb\WPWooCommercePrintifySync\Api\OpenAiApi;
use ApolloWeb\WPWooCommercePrintifySync\View\BladeTemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Security\Encryption;

class SettingsManager
{
    private BladeTemplateEngine $templateEngine;
    private PrintifyApi $printifyApi;
    private OpenAiApi $openAiApi;
    private Encryption $encryption;

    public function __construct(
        BladeTemplateEngine $templateEngine,
        PrintifyApi $printifyApi,
        OpenAiApi $openAiApi,
        Encryption $encryption
    ) {
        $this->templateEngine = $templateEngine;
        $this->printifyApi = $printifyApi;
        $this->openAiApi = $openAiApi;
        $this->encryption = $encryption;
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_test_printify_connection', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_wpwps_test_openai_connection', [$this, 'testOpenAiConnection']);
        add_action('wp_ajax_wpwps_save_settings', [$this, 'saveSettings']);
    }

    /**
     * Render settings page
     */
    public function render(): void
    {
        $data = [
            'printify_api_key' => $this->encryption->getEncryptedOption('wpwps_printify_api_key'),
            'printify_api_endpoint' => get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1'),
            'printify_shop_id' => get_option('wpwps_printify_shop_id', ''),
            'shops' => $this->getShopsList(),
            'openai_api_key' => $this->encryption->getEncryptedOption('wpwps_openai_api_key'),
            'openai_token_limit' => get_option('wpwps_openai_token_limit', 1000),
            'openai_spend_cap' => get_option('wpwps_openai_spend_cap', 10),
            'openai_temperature' => get_option('wpwps_openai_temperature', 0.7),
        ];
        
        // Use blade template
        echo $this->templateEngine->render('admin.settings', $data);
    }
    
    /**
     * Get the list of shops from Printify
     * 
     * @return array
     */
    private function getShopsList(): array
    {
        $api_key = $this->encryption->getEncryptedOption('wpwps_printify_api_key');
        $api_endpoint = get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1');
        
        if (empty($api_key)) {
            return [];
        }
        
        $response = $this->printifyApi->getShops($api_key, $api_endpoint);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    /**
     * Test Printify API connection
     */
    public function testPrintifyConnection(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : 'https://api.printify.com/v1';

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API key is required']);
            return;
        }

        $response = $this->printifyApi->testConnection($api_key, $api_endpoint);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        wp_send_json_success([
            'message' => 'Connection successful!',
            'shops' => $response['data']
        ]);
    }
    
    /**
     * Test OpenAI API connection and calculate estimated cost
     */
    public function testOpenAiConnection(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $token_limit = isset($_POST['token_limit']) ? intval($_POST['token_limit']) : 1000;
        $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.7;
        $spend_cap = isset($_POST['spend_cap']) ? floatval($_POST['spend_cap']) : 10;

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'OpenAI API key is required']);
            return;
        }

        $response = $this->openAiApi->testConnection($api_key);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }

        // Calculate estimated monthly cost
        $cost_per_token = 0.000002; // Approximate cost per token for GPT-3.5-Turbo
        $estimated_daily_tickets = 10; // Assumed average
        $avg_tokens_per_request = $token_limit * 0.75; // Average usage
        $daily_cost = $estimated_daily_tickets * $avg_tokens_per_request * $cost_per_token;
        $monthly_cost = $daily_cost * 30;

        wp_send_json_success([
            'message' => 'OpenAI API connection successful!',
            'estimated_monthly_cost' => '$' . number_format($monthly_cost, 2),
            'model_info' => $response['data']
        ]);
    }

    /**
     * Save plugin settings
     */
    public function saveSettings(): void
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        // Printify settings
        if (isset($_POST['printify_api_key'])) {
            $this->encryption->saveEncryptedOption('wpwps_printify_api_key', sanitize_text_field($_POST['printify_api_key']));
        }
        
        if (isset($_POST['printify_api_endpoint'])) {
            update_option('wpwps_printify_api_endpoint', esc_url_raw($_POST['printify_api_endpoint']));
        }
        
        if (isset($_POST['printify_shop_id'])) {
            update_option('wpwps_printify_shop_id', sanitize_text_field($_POST['printify_shop_id']));
        }

        // OpenAI settings
        if (isset($_POST['openai_api_key'])) {
            $this->encryption->saveEncryptedOption('wpwps_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
        }
        
        if (isset($_POST['openai_token_limit'])) {
            update_option('wpwps_openai_token_limit', intval($_POST['openai_token_limit']));
        }
        
        if (isset($_POST['openai_spend_cap'])) {
            update_option('wpwps_openai_spend_cap', floatval($_POST['openai_spend_cap']));
        }
        
        if (isset($_POST['openai_temperature'])) {
            update_option('wpwps_openai_temperature', floatval($_POST['openai_temperature']));
        }

        wp_send_json_success(['message' => 'Settings saved successfully']);
    }
}
