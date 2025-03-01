<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Class for handling direct API communication with Printify
 */
class PrintifyAPI {
    /**
     * API base URL for v2
     */
    const API_BASE = 'https://api.printify.com/v2';
    
    /**
     * API key option name
     */
    const API_KEY_OPTION = 'wpwps_printify_api_key';
    
    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new Logger('api');
    }
    
    /**
     * Get API key
     *
     * @return string|null The API key or null if not set
     */
    public function getApiKey() {
        return get_option(self::API_KEY_OPTION);
    }
    
    /**
     * Set API key
     *
     * @param string $key The API key
     * @return bool True if updated, false otherwise
     */
    public function setApiKey($key) {
        return update_option(self::API_KEY_OPTION, sanitize_text_field($key));
    }
    
    /**
     * Make a request to the Printify API
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array|WP_Error Response data or error
     */
    public function request($endpoint, $method = 'GET', $data = []) {
        $api_key = $this->getApiKey();
        
        if (empty($api_key)) {
            $this->logger->log('API request failed: No API key set', 'error');
            return new \WP_Error('no_api_key', 'No API key set');
        }
        
        $url = self::API_BASE . '/' . ltrim($endpoint, '/');
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        // For GET requests with query parameters
        if ($method === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        } 
        // For other request types that need a body
        else if ($method !== 'GET' && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }
        
        $this->logger->log("Making {$method} request to: {$url}", 'debug');
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->log('API request failed: ' . $response->get_error_message(), 'error');
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($body, true);
        
        // Log the response for debugging
        $this->logger->log("API response code: {$code}", 'debug');
        
        // Handle error responses according to Printify's format
        if ($code >= 400) {
            $error_message = isset($decoded_body['error']) ? $decoded_body['error'] : 'Unknown API error';
            $this->logger->log("API error: {$code} - {$error_message}", 'error');
            return new \WP_Error('api_error', $error_message, [
                'status' => $code,
                'response' => $decoded_body,
            ]);
        }
        
        return $decoded_body;
    }
    
    /**
     * Get shops from Printify API
     *
     * @return array|WP_Error Shops data or error
     */
    public function getShops() {
        return $this->request('shops.json');
    }
    
    /**
     * Get products from a specific shop
     *
     * @param int $shop_id Shop ID
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array|WP_Error Products data or error
     */
    public function getProducts($shop_id, $page = 1, $limit = 20) {
        return $this->request("shops/{$shop_id}/products.json", 'GET', [
            'page' => $page,
            'limit' => $limit
        ]);
    }
    
    /**
     * Get a specific product
     *
     * @param int $shop_id Shop ID
     * @param int $product_id Product ID
     * @return array|WP_Error Product data or error
     */
    public function getProduct($shop_id, $product_id) {
        return $this->request("shops/{$shop_id}/products/{$product_id}.json");
    }
    
    /**
     * Test the API connection
     *
     * @return bool|WP_Error True if connected, error otherwise
     */
    public function testConnection() {
        $result = $this->getShops();
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
}