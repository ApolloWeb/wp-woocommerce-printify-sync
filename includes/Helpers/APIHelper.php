<?php
/**
 * API Helper Class
 *
 * Helper functions for API requests
 *
 * @package WP_WooCommerce_Printify_Sync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

defined('ABSPATH') || exit;

/**
 * API Helper class
 */
class APIHelper {
    
    /**
     * API base URL
     *
     * @var string
     */
    private $api_url = 'https://api.printify.com/v1/';
    
    /**
     * API key
     *
     * @var string
     */
    private $api_key;
    
    /**
     * Constructor
     *
     * @param string $api_key Printify API key.
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }
    
    /**
     * Make a GET request to the API
     *
     * @param string $endpoint API endpoint.
     * @param array  $params   Query parameters.
     * @return array|WP_Error Response data or error
     */
    public function get($endpoint, $params = []) {
        return $this->request('GET', $endpoint, $params);
    }
    
    /**
     * Make a POST request to the API
     *
     * @param string $endpoint API endpoint.
     * @param array  $data     Post data.
     * @return array|WP_Error Response data or error
     */
    public function post($endpoint, $data = []) {
        return $this->request('POST', $endpoint, $data);
    }
    
    /**
     * Make a PUT request to the API
     *
     * @param string $endpoint API endpoint.
     * @param array  $data     Put data.
     * @return array|WP_Error Response data or error
     */
    public function put($endpoint, $data = []) {
        return $this->request('PUT', $endpoint, $data);
    }
    
    /**
     * Make a DELETE request to the API
     *
     * @param string $endpoint API endpoint.
     * @return array|WP_Error Response data or error
     */
    public function delete($endpoint) {
        return $this->request('DELETE', $endpoint);
    }
    
    /**
     * Make an API request
     *
     * @param string $method   HTTP method.
     * @param string $endpoint API endpoint.
     * @param array  $data     Request data or parameters.
     * @return array|WP_Error Response data or error
     */
    private function request($method, $endpoint, $data = []) {
        $url = $this->api_url . ltrim($endpoint, '/');
        
        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        if ($method === 'GET') {
            $url = add_query_arg($data, $url);
        } else {
            $args['body'] = wp_json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($code >= 400) {
            return new \WP_Error(
                'printify_api_error',
                isset($data['message']) ? $data['message'] : 'API Error',
                [
                    'status'  => $code,
                    'message' => $body,
                ]
            );
        }
        
        return $data;
    }
}