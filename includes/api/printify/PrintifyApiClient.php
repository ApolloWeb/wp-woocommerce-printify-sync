<?php
/**
 * Printify API Client
 *
 * Handles communication with the Printify API.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API\Printify
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API\Printify;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Admin\SettingsManager;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PrintifyApiClient {
    /**
     * Singleton instance
     *
     * @var PrintifyApiClient
     */
    private static $instance = null;
    
    /**
     * API URL
     *
     * @var string
     */
    private $api_url = 'https://api.printify.com/v1/';
    
    /**
     * API token
     *
     * @var string
     */
    private $api_token;
    
    /**
     * Current timestamp
     *
     * @var string
     */
    private $timestamp;
    
    /**
     * Current user
     *
     * @var string
     */
    private $user;
    
    /**
     * Get singleton instance
     *
     * @return PrintifyApiClient
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->timestamp = '2025-03-05 19:50:58';
        $this->user = 'ApolloWeb';
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Get API token from settings
        $settings = get_option('wpwprintifysync_settings', array());
        $this->api_token = isset($settings['printify_api_token']) ? $settings['printify_api_token'] : '';
    }
    
    /**
     * Set API token
     *
     * @param string $token API token
     */
    public function set_api_token($token) {
        $this->api_token = $token;
    }
    
    /**
     * Make API request to Printify
     *
     * @param string $endpoint API endpoint
     * @param array $args Request arguments
     * @return array Response data
     */
    public function request($endpoint, $args = array()) {
        // Check if API token is set
        if (empty($this->api_token)) {
            return array(
                'success' => false,
                'message' => 'API token not set',
                'code' => 401,
                'body' => null
            );
        }
        
        // Prepare request URL
        $url = $this->api_url . ltrim($endpoint, '/');
        
        // Default arguments
        $defaults = array(
            'method' => 'GET',
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'data_format' => 'body'
        );
        
        // Merge with user arguments
        $args = wp_parse_args($args, $defaults);
        
        // Prepare body if provided
        if (!empty($args['body']) && is_array($args['body'])) {
            $args['body'] = json_encode($args['body']);
        }
        
        // Log request (excluding sensitive info)
        Logger::get_instance()->debug('Printify API Request', array(
            'endpoint' => $endpoint,
            'method' => $args['method'],
            'timestamp' => $this->timestamp
        ));
        
        // Make request
        $response = wp_remote_request($url, $args);
        
        // Handle errors
        if (is_wp_error($response)) {
            Logger::get_instance()->error('Printify API Error', array(
                'endpoint' => $endpoint,
                'error' => $response->get_error_message(),
                'timestamp' => $this->timestamp
            ));
            
            return array(
                'success' => false,
                'code' => 0,
                'message' => $response->get_error_message(),
                'body' => null
            );
        }
        
        // Parse response
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        $success = $response_code >= 200 && $response_code < 300;
        
        if (!$success) {
            Logger::get_instance()->warning('Printify API Response Error', array(
                'endpoint' => $endpoint,
                'code' => $response_code,
                'message' => isset($response_data['message']) ? $response_data['message'] : 'Unknown error',
                'timestamp' => $this->timestamp
            ));
        }
        
        return array(
            'success' => $success,
            'code' => $response_code,
            'message' => isset($response_data['message']) ? $response_data['message'] : '',
            'body' => $response_data
        );
    }
    
    /**
     * Test API connection
     *
     * @return array Response data
     */
    public function test_connection() {
        return $this->request('shops.json');
    }
    
    /**
     * Get available blueprints (product templates)
     *
     * @return array Response data with blueprints
     */
    public function get_blueprints() {
        return $this->request('catalog/blueprints.json');
    }
    
    /**
     * Get blueprint details
     *
     * @param int $blueprint_id Blueprint ID
     * @return array Response data with blueprint details
     */
    public function get_blueprint($blueprint_id) {
        return $this->request("catalog/blueprints/{$blueprint_id}.json");
    }
    
    /**
     * Get print providers
     *
     * @return array Response data with print providers
     */
    public function get_print_providers() {
        return $this->request('catalog/print-providers.json');
    }
    
    /**
     * Get print provider details
     *
     * @param int $provider_id Provider ID
     * @return array Response data with provider details
     */
    public function get_print_provider($provider_id) {
        return $this->request("catalog/print-providers/{$provider_id}.json");
    }
    
    /**
     * Get printify products for a shop
     *
     * @param int $shop_id Shop ID
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Response data with products
     */
    public function get_products($shop_id, $page = 1, $limit = 50) {
        return $this->request("shops/{$shop_id}/products.json?page={$page}&limit={$limit}");
    }
    
    /**
     * Get printify product details
     *
     * @param int $shop_id Shop ID
     * @param string $product_id Product ID
     * @return array Response data with product details
     */
    public function get_product($shop_id, $product_id) {
        return $this->request("shops/{$shop_id}/products/{$product_id}.json");
    }
    
    /**
     * Get printify order details
     *
     * @param int $shop_id Shop ID
     * @param string $order_id Order ID
     * @return array Response data with order details
     */
    public function get_order($shop_id, $order_id) {
        return $this->request("shops/{$shop_id}/orders/{$order_id}.json");
    }
    
    /**
     * Get printify orders for a shop
     *
     * @param int $shop_id Shop ID
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Response data with orders
     */
    public function get_orders($shop_id, $page = 1, $limit = 50) {
        return $this->request("shops/{$shop_id}/orders.json?page={$page}&limit={$limit}");
    }
    
    /**
     * Create a new order in Printify
     *
     * @param int $shop_id Shop ID
     * @param array $order_data Order data
     * @return array Response data with order details
     */
    public function create_order($shop_id, $order_data) {
        return $this->request("shops/{$shop_id}/orders.json", array(
            'method' => 'POST',
            'body' => $order_data
        ));
    }
    
    /**
     * Get shipping information for a product
     *
     * @param int $shop_id Shop ID
     * @param string $product_id Product ID
     * @param string $variant_id Variant ID
     * @param array $address Shipping address
     * @return array Response data with shipping information
     */
    public function get_shipping_info($shop_id, $product_id, $variant_id, $address) {
        return $this->request("shops/{$shop_id}/products/{$product_id}/shipping.json", array(
            'method' => 'POST',
            'body' => array(
                'variant_ids' => array($variant_id),
                'address' => $address
            )
        ));
    }
}