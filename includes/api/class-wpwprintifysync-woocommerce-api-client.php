<?php
/**
 * WooCommerce API Client
 *
 * @package WP_WooCommerce_Printify_Sync
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce API Client Class
 */
class WPWPRINTIFYSYNC_WooCommerceApiClient {
    /**
     * Singleton instance
     *
     * @var WPWPRINTIFYSYNC_WooCommerceApiClient
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
     * @return WPWPRINTIFYSYNC_WooCommerceApiClient
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
        $this->timestamp = '2025-03-05 19:41:59';
        $this->user = 'ApolloWeb';
        $this->api_url = rest_url('wc/v3/');
        
        // Generate or get API credentials for internal use
        $this->setup_api_credentials();
    }
    
    /**
     * Setup API credentials for internal use
     */
    private function setup_api_credentials() {
        $this->consumer_key = get_option('wpwprintifysync_wc_api_key');
        $this->consumer_secret = get_option('wpwprintifysync_wc_api_secret');
        
        // If no credentials exist, generate them
        if (empty($this->consumer_key) || empty($this->consumer_secret)) {
            $this->generate_api_credentials();
        }
    }
    
    /**
     * Generate API credentials for internal use
     */
    private function generate_api_credentials() {
        // Find an admin user to associate the key with
        $admin_user = get_user_by('login', $this->user);
        if (!$admin_user) {
            $admin_users = get_users(array('role' => 'administrator', 'number' => 1));
            if (empty($admin_users)) {
                WPWPRINTIFYSYNC_Logger::get_instance()->error('Cannot generate API credentials - No admin user found', array(
                    'timestamp' => $this->timestamp
                ));
                return;
            }
            $admin_user = $admin_users[0];
        }
        
        // Generate API key
        $description = 'WP WooCommerce Printify Sync - Internal API Access';
        $permissions = 'read_write';
        
        // Create API key
        $consumer_key = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();
        
        // Store API key in database using WooCommerce's method if available
        if (function_exists('wc_create_new_customer_api_key')) {
            $key_id = wc_create_new_customer_api_key(array(
                'user_id' => $admin_user->ID,
                'description' => $description,
                'permissions' => $permissions
            ));
        } else {
            // Manual fallback if function doesn't exist
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'woocommerce_api_keys',
                array(
                    'user_id' => $admin_user->ID,
                    'description' => $description,
                    'permissions' => $permissions,
                    'consumer_key' => wc_api_hash($consumer_key),
                    'consumer_secret' => $consumer_secret,
                    'truncated_key' => substr($consumer_key, -7),
                ),
                array(
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                )
            );
        }
        
        // Save generated keys in options for future use
        update_option('wpwprintifysync_wc_api_key', $consumer_key);
        update_option('wpwprintifysync_wc_api_secret', $consumer_secret);
        
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        
        WPWPRINTIFYSYNC_Logger::get_instance()->info('Generated new WooCommerce API credentials', array(
            'user_id' => $admin_user->ID,
            'timestamp' => $this->timestamp
        ));
    }
    
    /**
     * Make API request to WooCommerce REST API
     *
     * @param string $endpoint API endpoint
     * @param array $args Request arguments
     * @return array Response data
     */
    public function request($endpoint, $args = array()) {
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
        if ($this->consumer_key && $this->consumer_secret) {
            $args['headers']['Authorization'] = 'Basic ' . base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        }
        
        // Prepare body if provided
        if (!empty($args['body']) && is_array($args['body'])) {
            $args['body'] = json_encode($args['body']);
        }
        
        // Log request (excluding sensitive info)
        WPWPRINTIFYSYNC_Logger::get_instance()->debug('WC API Request', array(
            'endpoint' => $endpoint,
            'method' => $args['method'],
            'timestamp' => $this->timestamp
        ));
        
        // Make request
        $response = wp_remote_request($url, $args);
        
        // Handle errors
        if (is_wp_error($response)) {
            WPWPRINTIFYSYNC_Logger::get_instance()->error('WC API Error', array(
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
            WPWPRINTIFYSYNC_Logger::get_instance()->warning('WC API Response Error', array(
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
     * Store multiple meta data for a product
     *
     * @param int $product_id WooCommerce product ID
     * @param array $meta_data Meta data as key-value pairs
     * @return bool Success status
     */
    public function store_product_meta_data($product_id, $meta_data) {
        // Prepare payload for bulk update
        $payload = array();
        
        foreach ($meta_data as $key => $value) {
            $payload[] = array(
                'key' => $key,
                'value' => $value
            );
        }
        
        // Make bulk update request
        $response = $this->request("products/{$product_id}/meta/batch", array(
            'method' => 'POST',
            'body' => array(
                'create' => $payload
            )
        ));
        
        if (!$response['success']) {
            WPWPRINTIFYSYNC_Logger::get_instance()->error('Failed to store product meta data', array(
                'product_id' => $product_id,
                'error' => $response['message'],
                'timestamp' => $this->timestamp
            ));
            return false;
        }
        
        return true;
    }
    
    /**
     * Store multiple meta data for an order
     *
     * @param int $order_id WooCommerce order ID
     * @param array $meta_data Meta data as key-value pairs
     * @return bool Success status
     */
    public function store_order_meta_data($order_id, $meta_data) {
        // Prepare payload for bulk update
        $payload = array();
        
        foreach ($meta_data as $key => $value) {
            $payload[] = array(
                'key' => $key,
                'value' => $value
            );
        }
        
        // Make bulk update request
        $response = $->request("orders/{$order_id}/meta/batch", array(
            'method' => 'POST',
            'body' => array(
                'create' => $payload
            )
        ));
        
        if (!$response['success']) {
            WPWPRINTIFYSYNC_Logger::get_instance()->error('Failed to store order meta data', array(
                'order_id' => $order_id,
                'error' => $response['message'],
                'timestamp' => $this->timestamp
            ));
            return false;
        }
        
        return true;
    }
}