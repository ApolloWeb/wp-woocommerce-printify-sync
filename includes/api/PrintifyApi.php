<?php
/**
 * Printify API client
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Utility\Logger;

class PrintifyApi {
    /**
     * @var string API key
     */
    private $apiKey;
    
    /**
     * @var string API mode
     */
    private $mode;
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var string API base URL
     */
    private $baseUrl;
    
    /**
     * Constructor
     *
     * @param string $apiKey API key
     * @param string $mode API mode (production or development)
     * @param Logger $logger Logger instance
     */
    public function __construct($apiKey, $mode, Logger $logger) {
        $this->apiKey = $apiKey;
        $this->mode = $mode;
        $this->logger = $logger;
        
        $this->baseUrl = ($mode === 'production') 
            ? 'https://api.printify.com' 
            : 'https://api.sandbox.printify.com';
    }
    
    /**
     * Send request to Printify API
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array Response
     */
    public function request($endpoint, $method = 'GET', $data = []) {
        $url = $this->baseUrl . $endpoint;
        
        $args = [
            'method'    => $method,
            'timeout'   => 45,
            'headers'   => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
        ];
        
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
        }
        
        $this->logger->debug('Sending request to Printify API', [
            'url' => $url,
            'method' => $method
        ]);
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->error('Printify API error', [
                'error' => $response->get_error_message()
            ]);
            
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code >= 400) {
            $this->logger->error('Printify API error response', [
                'code' => $response_code,
                'body' => $response_body
            ]);
            
            return [
                'success' => false,
                'code' => $response_code,
                'error' => $response_body
            ];
        }
        
        return [
            'success' => true,
            'code' => $response_code,
            'data' => $response_body
        ];
    }
    
    /**
     * Test API connection
     *
     * @return array Test result
     */
    public function testConnection() {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => __('API key is not configured', 'wp-woocommerce-printify-sync')
            ];
        }
        
        $response = $this->request('/v2/shops.json');
        
        if (!$response['success']) {
            return [
                'success' => false,
                'message' => isset($response['error']) ? $response['error'] : __('Connection failed', 'wp-woocommerce-printify-sync')
            ];
        }
        
        return [
            'success' => true,
            'message' => __('Connection successful', 'wp-woocommerce-printify-sync'),
            'data' => $response['data']
        ];
    }
    
    /**
     * Get shipping profiles
     *
     * @return array Shipping profiles
     */
    public function getShippingProfiles() {
        return $this->request('/v2/shipping_profiles.json');
    }
    
    /**
     * Get products
     *
     * @param int $shopId Shop ID
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Products
     */
    public function getProducts($shopId, $page = 1, $limit = 20) {
        return $this->request("/v2/shops/{$shopId}/products.json?page={$page}&limit={$limit}");
    }
    
    /**
     * Get product
     *
     * @param int $shopId Shop ID
     * @param int $productId Product ID
     * @return array Product
     */
    public function getProduct($shopId, $productId) {
        return $this->request("/v2/shops/{$shopId}/products/{$productId}.json");
    }
    
    /**
     * Create order
     *
     * @param int $shopId Shop ID
     * @param array $orderData Order data
     * @return array Order creation result
     */
    public function createOrder($shopId, $orderData) {
        return $this->request("/v2/shops/{$shopId}/orders.json", 'POST', $orderData);
    }
    
    /**
     * Get order
     *
     * @param int $shopId Shop ID
     * @param int $orderId Order ID
     * @return array Order
     */
    public function getOrder($shopId, $orderId) {
        return $this->request("/v2/shops/{$shopId}/orders/{$orderId}.json");
    }
}