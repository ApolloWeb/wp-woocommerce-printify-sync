<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;
use WP_Error;

class PrintifyAPI
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
        $response = $this->makeRequest('shops.json');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Get products for a shop
     * 
     * @param string $shopId The shop ID
     * @return array|WP_Error Products or error
     */
    public function getProducts(string $shopId)
    {
        $response = $this->makeRequest("shops/{$shopId}/products.json");
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
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
        $response = $this->makeRequest("shops/{$shopId}/products/{$productId}.json");
        
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
        $url = $baseUrl . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        if (!empty($data) && in_array($method, ['POST', 'PUT'])) {
            $args['body'] = wp_json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $responseCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);
        
        if ($responseCode < 200 || $responseCode >= 300) {
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
            return new WP_Error('json_parse_error', __('Failed to parse API response.', 'wp-woocommerce-printify-sync'));
        }
        
        return $data;
    }
}
