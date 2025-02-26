<?php

namespace ApolloWeb\WooCommercePrintifySync;

/**
 * API Class for Printify API interactions
 */

class Api {
    /**
     * API Base URL
     * @var string
     */
    private $api_url = 'https://api.printify.com/v1/';
    
    /**
     * API Key
     * @var string
     */
    private $api_key = '';

    /**
     * Constructor
     * 
     * @param string $api_key Printify API key
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Make a request to the Printify API
     * 
     * @param string $endpoint API endpoint
     * @param string $method HTTP method (GET, POST, etc.)
     * @param array $data Request data
     * @return array|WP_Error Response data or error
     */
    public function request($endpoint, $method = 'GET', $data = []) {
        // Build the request URL
        $url = $this->api_url . ltrim($endpoint, '/');
        
        // Setup request args
        $args = [
            'method'    => $method,
            'headers'   => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'timeout'   => 30,
        ];
        
        // Add body data for non-GET requests
        if ($method !== 'GET' && !empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        // Make the request
        $response = wp_remote_request($url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Get response code and body
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        // Check for API errors
        if ($response_code >= 400) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown API error';
            return new \WP_Error('api_error', $error_message, ['status' => $response_code]);
        }
        
        return $response_data;
    }

    /**
     * Get shops from the API
     * 
     * @return array|WP_Error Shops data or error
     */
    public function getShops() {
        return $this->request('shops.json');
    }

    /**
     * Get products from a specific shop
     * 
     * @param string $shop_id Shop ID
     * @param int $limit Number of products to fetch
     * @return array|WP_Error Products data or error
     */
    public function getProducts($shop_id, $limit = 10) {
        return $this->request("shops/{$shop_id}/products.json?limit={$limit}");
    }

    /**
     * Get a specific product
     * 
     * @param string $shop_id Shop ID
     * @param string $product_id Product ID
     * @return array|WP_Error Product data or error
     */
    public function getProduct($shop_id, $product_id) {
        return $this->request("shops/{$shop_id}/products/{$product_id}.json");
    }
}