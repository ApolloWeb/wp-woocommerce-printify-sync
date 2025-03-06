<?php
/**
 * WooCommerce API Client
 *
 * Handles communication with the WooCommerce REST API.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API\WooCommerce
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API\WooCommerce;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WooCommerceApiClient {
    /**
     * Singleton instance
     *
     * @var WooCommerceApiClient
     */
    private static $instance = null;
    
    /**
     * API URL
     *
     * @var string
     */
    private $api_url;
    
    /**
     * Consumer key
     *
     * @var string
     */
    private $consumer_key;
    
    /**
     * Consumer secret
     *
     * @var string
     */
    private $consumer_secret;
    
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
     * @return WooCommerceApiClient
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
        $this->timestamp = '2025-03-05 20:28:57';
        $this->user = 'ApolloWeb';
        $this->api_url = get_rest_url(null, 'wc/v3/');
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Get API credentials from settings
        $settings = get_option('wpwprintifysync_settings', array());
        
        // If API credentials are not set, generate them
        if (empty($settings['wc_consumer_key']) || empty($settings['wc_consumer_secret'])) {
            $this->generate_api_keys();
        } else {
            $this->consumer_key = $settings['wc_consumer_key'];
            $this->consumer_secret = $settings['wc_consumer_secret'];
        }
    }
    
    /**
     * Generate WooCommerce API keys
     */
    private function generate_api_keys() {
        // Check if current user can manage WooCommerce
        if (!current_user_can('manage_woocommerce')) {
            Logger::get_instance()->error('Failed to generate WooCommerce API keys - Insufficient permissions', array(
                'timestamp' => $this->timestamp
            ));
            return;
        }
        
        // Create API key
        $description = 'Printify Sync Plugin - ' . date('Y-m-d H:i:s');
        $permissions = 'read_write';
        $user_id = get_current_user_id();
        
        $data = array(
            'user_id' => $user_id,
            'description' => $description,
            'permissions' => $permissions
        );
        
        $response = wp_insert_post(array(
            'post_title' => 'Printify Sync',
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'wc_api_key',
            'user_id' => $user_id,
            'meta_input' => array(
                'description' => $description,
                'permissions' => $permissions,
            )
        ));
        
        // If API key creation failed
        if (is_wp_error($response)) {
            Logger::get_instance()->error('Failed to generate WooCommerce API keys', array(
                'error' => $response->get_error_message(),
                'timestamp' => $this->timestamp
            ));
            return;
        }
        
        // Generate consumer key and secret
        $consumer_key = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();
        
        // Store API keys
        update_post_meta($response, 'consumer_key', $consumer_key);
        update_post_meta($response, 'consumer_secret', $consumer_secret);
        
        // Update plugin settings
        $settings = get_option('wpwprintifysync_settings', array());
        $settings['wc_consumer_key'] = $consumer_key;
        $settings['wc_consumer_secret'] = $consumer_secret;
        update_option('wpwprintifysync_settings', $settings);
        
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        
        Logger::get_instance()->info('WooCommerce API keys generated', array(
            'key_id' => $response,
            'timestamp' => $this->timestamp
        ));
    }
    
    /**
     * Make API request to WooCommerce
     *
     * @param string $endpoint API endpoint
     * @param array $args Request arguments
     * @return array Response data
     */
    public function request($endpoint, $args = array()) {
        // Check if API credentials are set
        if (empty($this->consumer_key) || empty($this->consumer_secret)) {
            return array(
                'success' => false,
                'message' => 'API credentials not set',
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
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'data_format' => 'body'
        );
        
        // Merge with user arguments
        $args = wp_parse_args($args, $defaults);
        
        // Add authentication
        $args['headers']['Authorization'] = 'Basic ' . base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        
        // Prepare body if provided
        if (!empty($args['body']) && is_array($args['body'])) {
            $args['body'] = json_encode($args['body']);
        }
        
        // Log request (excluding sensitive info)
        Logger::get_instance()->debug('WooCommerce API Request', array(
            'endpoint' => $endpoint,
            'method' => $args['method'],
            'timestamp' => $this->timestamp
        ));
        
        // Make request
        $response = wp_remote_request($url, $args);
        
        // Handle errors
        if (is_wp_error($response)) {
            Logger::get_instance()->error('WooCommerce API Error', array(
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
            Logger::get_instance()->warning('WooCommerce API Response Error', array(
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
     * Store product meta data
     *
     * @param int $product_id Product ID
     * @param array $meta_data Meta data
     * @return bool Success status
     */
    public function store_product_meta_data($product_id, $meta_data) {
        if (empty($product_id) || empty($meta_data)) {
            return false;
        }
        
        // Use WooCommerce API to update product meta data
        if (function_exists('wc_get_product')) {
            // If we're in a WordPress environment with WooCommerce active, use the direct method
            $product = wc_get_product($product_id);
            
            if (!$product) {
                return false;
            }
            
            foreach ($meta_data as $key => $value) {
                $product->update_meta_data($key, $value);
            }
            
            $product->save();
            return true;
        } else {
            // Otherwise, use the REST API
            $meta_update = array();
            
            foreach ($meta_data as $key => $value) {
                $meta_update[] = array(
                    'key' => $key,
                    'value' => $value
                );
            }
            
            $response = $this->request("products/{$product_id}", array(
                'method' => 'PUT',
                'body' => array(
                    'meta_data' => $meta_update
                )
            ));
            
            return $response['success'];
        }
    }
    
    /**
     * Get product by SKU
     *
     * @param string $sku Product SKU
     * @return array|bool Product data or false on failure
     */
    public function get_product_by_sku($sku) {
        if (empty($sku)) {
            return false;
        }
        
        // Search for products with the given SKU
        $response = $this->request('products', array(
            'method' => 'GET',
            'query' => array(
                'sku' => $sku
            )
        ));
        
        if (!$response['success'] || empty($response['body'])) {
            return false;
        }
        
        // Return the first product with matching SKU
        return $response['body'][0] ?? false;
    }
    
    /**
     * Create product category
     *
     * @param string $name Category name
     * @param string $slug Category slug
     * @param string $description Category description
     * @param int $parent Parent category ID
     * @return int|bool Category ID or false on failure
     */
    public function create_product_category($name, $slug = '', $description = '', $parent = 0) {
        if (empty($name)) {
            return false;
        }
        
        $data = array(
            'name' => $name
        );
        
        if (!empty($slug)) {
            $data['slug'] = $slug;
        }
        
        if (!empty($description)) {
            $data['description'] = $description;
        }
        
        if ($parent > 0) {
            $data['parent'] = $parent;
        }
        
        $response = $this->request('products/categories', array(
            'method' => 'POST',
            'body' => $data
        ));
        
        if (!$response['success'] || empty($response['body'])) {
            return false;
        }
        
        return $response['body']['id'] ?? false;
    }
    
    /**
     * Update product stock
     *
     * @param int $product_id Product ID
     * @param int $stock_quantity Stock quantity
     * @return bool Success status
     */
    public function update_product_stock($product_id, $stock_quantity) {
        if (empty($product_id)) {
            return false;
        }
        
        $response = $this->request("products/{$product_id}", array(
            'method' => 'PUT',
            'body' => array(
                'stock_quantity' => $stock_quantity,
                'manage_stock' => true
            )
        ));
        
        return $response['success'];
    }
    
    /**
     * Update order status
     *
     * @param int $order_id Order ID
     * @param string $status Order status
     * @return bool Success status
     */
    public function update_order_status($order_id, $status) {
        if (empty($order_id) || empty($status)) {
            return false;
        }
        
        $response = $this->request("orders/{$order_id}", array(
            'method' => 'PUT',
            'body' => array(
                'status' => $status
            )
        ));
        
        return $response['success'];
    }
    
    /**
     * Add order note
     *
     * @param int $order_id Order ID
     * @param string $note Note content
     * @param bool $customer_note Whether note is visible to customers
     * @return int|bool Note ID or false on failure
     */
    public function add_order_note($order_id, $note, $customer_note = false) {
        if (empty($order_id) || empty($note)) {
            return false;
        }
        
        $response = $this->request("orders/{$order_id}/notes", array(
            'method' => 'POST',
            'body' => array(
                'note' => $note,
                'customer_note' => $customer_note
            )
        ));
        
        if (!$response['success'] || empty($response['body'])) {
            return false;
        }
        
        return $response['body']['id'] ?? false;
    }
    
    /**
     * Get order data
     *
     * @param int $order_id Order ID
     * @return array|bool Order data or false on failure
     */
    public function get_order($order_id) {
        if (empty($order_id)) {
            return false;
        }
        
        $response = $this->request("orders/{$order_id}");
        
        if (!$response['success'] || empty($response['body'])) {
            return false;
        }
        
        return $response['body'];
    }
    
    /**
     * Set API credentials
     *
     * @param string $consumer_key Consumer key
     * @param string $consumer_secret Consumer secret
     */
    public function set_api_credentials($consumer_key, $consumer_secret) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
    }
}