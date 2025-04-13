<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Api;

/**
 * Printify API interaction class
 */
class PrintifyApi {
    /**
     * API Key
     *
     * @var string
     */
    private $api_key;
    
    /**
     * API Endpoint
     *
     * @var string
     */
    private $api_endpoint;
    
    /**
     * Constructor
     *
     * @param string $api_key      API Key
     * @param string $api_endpoint API Endpoint
     */
    public function __construct($api_key, $api_endpoint = null) {
        $this->api_key = $api_key;
        $this->api_endpoint = trailingslashit($api_endpoint ?: WPWPS_PRINTIFY_API_BASE);
    }
    
    /**
     * Get products from Printify
     *
     * @param string $shop_id Shop ID
     * @param array  $params  Query parameters
     * @return array|WP_Error Products array or WP_Error
     */
    public function get_products($shop_id, $params = []) {
        return $this->request('GET', "shops/{$shop_id}/products.json", $params);
    }
    
    /**
     * Get a single product from Printify
     *
     * @param string $shop_id    Shop ID
     * @param string $product_id Product ID
     * @return array|WP_Error Product array or WP_Error
     */
    public function get_product($shop_id, $product_id) {
        return $this->request('GET', "shops/{$shop_id}/products/{$product_id}.json");
    }
    
    /**
     * Register external product ID with Printify
     *
     * @param string $shop_id          Shop ID
     * @param string $printify_id      Printify Product ID
     * @param string $external_id      WooCommerce Product ID
     * @return array|WP_Error Response or WP_Error
     */
    public function register_external_product($shop_id, $printify_id, $external_id) {
        $data = [
            'external_id' => $external_id,
            'handle' => get_permalink($external_id) ?: '',
        ];
        
        return $this->request('PUT', "shops/{$shop_id}/products/{$printify_id}/external-id.json", $data);
    }
    
    /**
     * Get print providers from Printify
     *
     * @return array|WP_Error Print providers array or WP_Error
     */
    public function get_print_providers() {
        return $this->request('GET', 'print-providers.json');
    }
    
    /**
     * Get all available webhooks
     *
     * @return array|WP_Error Webhooks array or WP_Error
     */
    public function get_webhooks() {
        return $this->request('GET', 'webhooks.json');
    }
    
    /**
     * Register a webhook with Printify
     *
     * @param string $event Event to subscribe to
     * @param string $url   URL to send webhook to
     * @return array|WP_Error Response or WP_Error
     */
    public function register_webhook($event, $url) {
        $data = [
            'event' => $event,
            'url' => $url
        ];
        
        return $this->request('POST', 'webhooks.json', $data);
    }
    
    /**
     * Delete a webhook
     *
     * @param string $webhook_id Webhook ID
     * @return array|WP_Error Response or WP_Error
     */
    public function delete_webhook($webhook_id) {
        return $this->request('DELETE', "webhooks/{$webhook_id}.json");
    }
    
    /**
     * Make request to Printify API
     *
     * @param string $method   HTTP method
     * @param string $endpoint API endpoint
     * @param array  $data     Request data or query parameters
     * @return array|WP_Error Response data or WP_Error
     */
    private function request($method, $endpoint, $data = []) {
        $url = $this->api_endpoint . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        if ($method === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        } else if ($method !== 'GET' && !empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Log the API request for debugging
        $this->log_api_request($method, $endpoint, $data, $status_code, $response);
        
        if ($status_code < 200 || $status_code >= 300) {
            $message = isset($data['message']) ? $data['message'] : 'Unknown API error';
            return new \WP_Error('api_error', sprintf(__('API Error: %s (Status: %d)', 'wp-woocommerce-printify-sync'), $message, $status_code));
        }
        
        return $data;
    }
    
    /**
     * Log API requests for debugging
     *
     * @param string $method      HTTP method
     * @param string $endpoint    API endpoint
     * @param mixed  $data        Request data
     * @param int    $status_code Response status code
     * @param array  $response    Full response
     */
    private function log_api_request($method, $endpoint, $data, $status_code, $response) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log = [
            'timestamp' => current_time('mysql'),
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data,
            'status_code' => $status_code,
            'response' => wp_remote_retrieve_body($response),
        ];
        
        // Sanitize sensitive data
        if (strpos($endpoint, 'api_key') !== false) {
            $log['data'] = '[REDACTED]';
        }
        
        $logger = wc_get_logger();
        $logger->debug(print_r($log, true), ['source' => 'printify_api']);
    }
}
