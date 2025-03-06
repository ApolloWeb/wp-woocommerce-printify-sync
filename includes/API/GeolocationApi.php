<?php
/**
 * Geolocation API client
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Utility\Logger;

class GeolocationApi {
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
        
        // Using ipstack as an example geolocation API
        $this->baseUrl = 'http://api.ipstack.com/';
    }
    
    /**
     * Send request to Geolocation API
     *
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @return array Response
     */
    public function request($endpoint, $params = []) {
        $params['access_key'] = $this->apiKey;
        
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);
        
        $this->logger->debug('Sending request to Geolocation API', [
            'url' => $url
        ]);
        
        $response = wp_remote_get($url, [
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            $this->logger->error('Geolocation API error', [
                'error' => $response->get_error_message()
            ]);
            
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code >= 400 || (isset($response_body['success']) && $response_body['success'] === false)) {
            $this->logger->error('Geolocation API error response', [
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
     * Get location by IP
     *
     * @param string $ip IP address
     * @return array Location data
     */
    public function getLocationByIp($ip) {
        return $this->request($ip);
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
        
        $response = $this->request('check', ['output' => 'json']);
        
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
}