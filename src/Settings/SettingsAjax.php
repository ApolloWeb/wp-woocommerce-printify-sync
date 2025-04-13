<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Settings;

/**
 * Settings AJAX Handler
 */
class SettingsAjax {
    /**
     * Register AJAX handlers
     * 
     * @return void
     */
    public function registerAjaxHandlers() {
        add_action('wp_ajax_wpwps_test_printify_connection', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_wpwps_save_printify_settings', [$this, 'savePrintifySettings']);
        add_action('wp_ajax_wpwps_test_chatgpt_connection', [$this, 'testChatGptConnection']);
        add_action('wp_ajax_wpwps_save_chatgpt_settings', [$this, 'saveChatGptSettings']);
        add_action('wp_ajax_wpwps_estimate_chatgpt_cost', [$this, 'estimateChatGptCost']);
    }
    
    /**
     * Test Printify API connection
     * 
     * @return void
     */
    public function testPrintifyConnection() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-settings-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Get API key
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Default endpoint if not provided
        if (empty($api_endpoint)) {
            $api_endpoint = 'https://api.printify.com/v1/';
        }
        
        // Test connection to Printify API
        $settings = new SettingsService();
        $response = $settings->testPrintifyConnection($api_key, $api_endpoint);
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message()
            ]);
        }
        
        wp_send_json_success([
            'message' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
            'shops' => $response
        ]);
    }
    
    /**
     * Save Printify settings
     * 
     * @return void
     */
    public function savePrintifySettings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-settings-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Get settings
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $api_endpoint = isset($_POST['api_endpoint']) ? esc_url_raw($_POST['api_endpoint']) : '';
        $shop_id = isset($_POST['shop_id']) ? sanitize_text_field($_POST['shop_id']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Default endpoint if not provided
        if (empty($api_endpoint)) {
            $api_endpoint = 'https://api.printify.com/v1/';
        }
        
        // Save settings
        $settings = new SettingsService();
        $result = $settings->savePrintifySettings($api_key, $api_endpoint, $shop_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
        }
        
        wp_send_json_success([
            'message' => __('Settings saved successfully!', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Test ChatGPT API connection
     * 
     * @return void
     */
    public function testChatGptConnection() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-settings-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Get API key
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Test connection to ChatGPT API
        $settings = new SettingsService();
        $response = $settings->testChatGptConnection($api_key);
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message()
            ]);
        }
        
        wp_send_json_success([
            'message' => __('Connection successful!', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Save ChatGPT settings
     * 
     * @return void
     */
    public function saveChatGptSettings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-settings-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Get settings
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $monthly_cap = isset($_POST['monthly_cap']) ? intval($_POST['monthly_cap']) : 0;
        $token_limit = isset($_POST['token_limit']) ? intval($_POST['token_limit']) : 0;
        $temperature = isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0;
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Save settings
        $settings = new SettingsService();
        $result = $settings->saveChatGptSettings($api_key, $monthly_cap, $token_limit, $temperature);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
        }
        
        wp_send_json_success([
            'message' => __('Settings saved successfully!', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Estimate ChatGPT cost
     * 
     * @return void
     */
    public function estimateChatGptCost() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-settings-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'wp-woocommerce-printify-sync')]);
        }
        
        // Get settings
        $token_limit = isset($_POST['token_limit']) ? intval($_POST['token_limit']) : 0;
        $estimated_tickets = isset($_POST['estimated_tickets']) ? intval($_POST['estimated_tickets']) : 0;
        
        // Calculate estimated cost
        $settings = new SettingsService();
        $cost = $settings->estimateChatGptCost($token_limit, $estimated_tickets);
        
        wp_send_json_success([
            'estimated_cost' => $cost
        ]);
    }
}
