<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

/**
 * Printify API Implementation
 */
class PrintifyApi implements PrintifyApiInterface {
    /**
     * API token
     *
     * @var string
     */
    private $api_token;
    
    /**
     * Shop ID
     *
     * @var string
     */
    private $shop_id;
    
    /**
     * Settings instance
     * 
     * @var Settings
     */
    private $settings;
    
    /**
     * Constructor
     * 
     * @param Settings|null $settings Optional settings instance
     */
    public function __construct($settings = null) {
        if ($settings instanceof Settings) {
            $this->settings = $settings;
        } else {
            // Create settings instance if not provided
            $this->settings = new Settings();
        }
        
        // Initialize settings
        $this->initialize_api_credentials();
    }
    
    /**
     * Initialize API credentials from settings
     */
    private function initialize_api_credentials() {
        // Use get_option method instead of non-existent get_printify_api_key
        $this->api_token = $this->settings->get_option('api_key', '');
        $this->shop_id = $this->settings->get_option('shop_id', '');
    }
    
    /**
     * Make an API request
     *
     * @param string $endpoint
     * @param string $method
     * @param array $body
     * @return array|false
     */
    private function request($endpoint, $method = 'GET', $body = []) {
        // Check if credentials are set
        if (empty($this->api_token)) {
            $this->log_error('API token is not set. Please configure Printify API settings.');
            return false;
        }
        
        $url = WPWPS_PRINTIFY_API_URL . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        if (!empty($body) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($body);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->log_error('API request failed: ' . $response->get_error_message());
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            $this->handleError($response);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Get products from Printify API
     *
     * @param array $params Optional parameters for filtering/pagination
     * @return array|false
     */
    public function getProducts($params = []) {
        $endpoint = "shops/{$this->shop_id}/products.json";
        
        // Add query parameters if provided
        if (!empty($params)) {
            $query_string = http_build_query($params);
            $endpoint .= '?' . $query_string;
        }
        
        return $this->request($endpoint);
    }
    
    /**
     * Get a single product by ID
     *
     * @param int $product_id
     * @return array|false
     */
    public function getProduct($product_id) {
        return $this->request("shops/{$this->shop_id}/products/{$product_id}.json");
    }
    
    /**
     * Create a webhook subscription
     *
     * @param string $event
     * @param string $url
     * @return bool
     */
    public function createWebhook($event, $url) {
        $data = [
            'topic' => $event,
            'url' => $url,
        ];
        
        $result = $this->request("shops/{$this->shop_id}/webhooks.json", 'POST', $data);
        return !empty($result);
    }
    
    /**
     * Handle API errors
     *
     * @param mixed $response
     * @return void
     */
    public function handleError($response) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!empty($data['message'])) {
            $this->log_error('Printify API Error: ' . $data['message']);
        }
    }
    
    /**
     * Log error message
     *
     * @param string $message
     * @return void
     */
    private function log_error($message) {
        if (function_exists('error_log')) {
            error_log('[Printify Sync] ' . $message);
        }
        
        // Check if we should store error in database
        $log_level = $this->settings->get_option('log_level', 'error');
        if ($log_level !== 'none') {
            // Add to plugin's error log
            // This would be implemented in a dedicated logging class
        }
    }
    
    /**
     * Check if API is configured correctly
     *
     * @return bool
     */
    public function is_api_configured() {
        return !empty($this->api_token) && !empty($this->shop_id);
    }
    
    /**
     * Update API credentials
     * 
     * @param string $api_token
     * @param string $shop_id
     * @return void
     */
    public function update_credentials($api_token, $shop_id) {
        $this->api_token = $api_token;
        $this->shop_id = $shop_id;
    }
}
