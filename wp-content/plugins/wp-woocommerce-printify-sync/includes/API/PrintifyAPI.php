<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;
use WP_Error;

class PrintifyAPI implements PrintifyAPIInterface
{
    /**
     * The settings object
     * 
     * @var Settings
     */
    private Settings $settings;
    
    /**
     * Constructor
     * 
     * @param Settings $settings The settings object
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }
    
    /**
     * Get shops from Printify API
     * 
     * @return array|WP_Error Shops or error
     */
    public function getShops()
    {
        $response = $this->makeRequest('shops.json', 'GET');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Get products for a shop
     * 
     * @param string $shopId The shop ID
     * @param array $params Additional query parameters (limit, page)
     * @return array|WP_Error Products or error
     */
    public function getProducts(string $shopId, array $params = [])
    {
        // Set default parameters if not provided
        $params = wp_parse_args($params, [
            'limit' => 50, // Max allowed by API
            'page' => 1
        ]);
        
        // Make the request with GET method and specified parameters
        $response = $this->makeRequest("shops/{$shopId}/products.json", 'GET', $params);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Check if we got a properly structured response with 'data' field
        if (!isset($response['data']) && !is_array($response)) {
            return new WP_Error(
                'invalid_response',
                __('Invalid response format from Printify API.', 'wp-woocommerce-printify-sync')
            );
        }
        
        // Return just the data array if it's a paginated response
        return isset($response['data']) ? $response['data'] : $response;
    }
    
    /**
     * Get all products for a shop (handling pagination automatically)
     * 
     * @param string $shopId The shop ID
     * @param int $limit Items per page (max 50)
     * @return array|WP_Error All products or error
     */
    public function getAllProducts(string $shopId, int $limit = 50)
    {
        $allProducts = [];
        $page = 1;
        $morePages = true;
        
        while ($morePages) {
            $response = $this->makeRequest("shops/{$shopId}/products.json", 'GET', [
                'limit' => $limit,
                'page' => $page
            ]);
            
            if (is_wp_error($response)) {
                return $response;
            }
            
            // Check if we have a paginated response
            if (isset($response['data']) && is_array($response['data'])) {
                $allProducts = array_merge($allProducts, $response['data']);
                
                // Check if there are more pages
                $morePages = isset($response['next_page_url']) && !empty($response['next_page_url']);
                $page++;
            } else {
                // If it's not a paginated response, just add the products and exit
                if (is_array($response)) {
                    $allProducts = array_merge($allProducts, $response);
                }
                $morePages = false;
            }
            
            // Prevent infinite loops by capping at 100 pages (5000 products)
            if ($page > 100) {
                break;
            }
        }
        
        return $allProducts;
    }
    
    /**
     * Get a specific product
     * 
     * @param string $shopId The shop ID
     * @param string $productId The product ID
     * @return array|WP_Error Product or error
     */
    public function getProduct(string $shopId, string $productId)
    {
        // Make sure we're explicitly using GET method
        $response = $this->makeRequest("shops/{$shopId}/products/{$productId}.json", 'GET');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Make a request to the Printify API
     * 
     * @param string $endpoint The API endpoint
     * @param string $method The HTTP method
     * @param array $data The data to send
     * @return array|WP_Error Response or error
     */
    private function makeRequest(string $endpoint, string $method = 'GET', array $data = [])
    {
        $apiKey = $this->settings->getApiKey();
        
        if (empty($apiKey)) {
            return new WP_Error('invalid_api_key', __('API key is missing.', 'wp-woocommerce-printify-sync'));
        }
        
        $baseUrl = $this->settings->getApiEndpoint();
        // Remove trailing slash from base URL if present
        $baseUrl = rtrim($baseUrl, '/');
        
        // Make sure endpoint doesn't start with a slash
        $endpoint = ltrim($endpoint, '/');
        
        $url = $baseUrl . '/' . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ],
            'timeout' => 60, // Increased timeout for potentially slow API responses
        ];
        
        // For GET requests with data, add as query parameters
        if ($method === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        } else if (!empty($data) && in_array($method, ['POST', 'PUT'])) {
            // Only add Content-Type header and body for non-GET requests
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($data);
        }
        
        // Log the request for debugging
        error_log('WPWPS API Request: ' . $url . ' (Method: ' . $method . ')');
        
        // Use the specific WordPress functions based on HTTP method
        if ($method === 'GET') {
            $response = wp_remote_get($url, $args);
        } else {
            $response = wp_remote_request($url, $args);
        }
        
        if (is_wp_error($response)) {
            error_log('WPWPS API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $responseCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);
        
        error_log('WPWPS API Response Code: ' . $responseCode);
        
        if ($responseCode < 200 || $responseCode >= 300) {
            error_log('WPWPS API Error Response: ' . $responseBody);
            
            return new WP_Error(
                'api_error',
                sprintf(
                    __('API Error: %s (Code: %s)', 'wp-woocommerce-printify-sync'),
                    $responseBody,
                    $responseCode
                )
            );
        }
        
        $data = json_decode($responseBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('WPWPS API JSON Parse Error: ' . json_last_error_msg());
            return new WP_Error('json_parse_error', __('Failed to parse API response.', 'wp-woocommerce-printify-sync'));
        }
        
        return $data;
    }
}
