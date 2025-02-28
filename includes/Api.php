/**
 * Api class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

namespace ApolloWeb\WooCommercePrintifySync;


class Api {
    private $api_url = 'https://api.printify.com/v1/';
    
    private $api_key = '';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

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

    public function getShops() {
        return $this->request('shops.json');
    }

    public function getProducts($shop_id, $limit = 10) {
        return $this->request("shops/{$shop_id}/products.json?limit={$limit}");
    }

    public function getProduct($shop_id, $product_id) {
        return $this->request("shops/{$shop_id}/products/{$product_id}.json");
    }
}
