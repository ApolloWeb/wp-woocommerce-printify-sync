<?php
/**
 * Printify API Client
 *
 * Handles all communication with the Printify API.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Api
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\ApiClientInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Utils\RetryStrategy;

/**
 * PrintifyClient Class
 */
class PrintifyClient implements ApiClientInterface {
    /**
     * API Base URL
     *
     * @var string
     */
    private $api_url = 'https://api.printify.com/v1/';

    /**
     * API Key
     *
     * @var string
     */
    private $api_key;

    /**
     * Shop ID
     *
     * @var string
     */
    private $shop_id;

    /**
     * Retry strategy
     *
     * @var RetryStrategy
     */
    private $retry_strategy;
    
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger Logger
     */
    public function __construct(LoggerInterface $logger = null) {
        $this->api_key = $this->getApiKey();
        $this->shop_id = $this->getShopId();
        $this->retry_strategy = new RetryStrategy();
        $this->logger = $logger;
    }

    /**
     * Get API key from settings
     *
     * @return string
     */
    private function getApiKey() {
        return get_option('apolloweb_printify_api_key', '');
    }

    /**
     * Get Shop ID from settings
     *
     * @return string
     */
    private function getShopId() {
        return get_option('apolloweb_printify_shop_id', '');
    }

    /**
     * Make API request
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array  $data Request data
     * @param array  $query_params Query parameters
     * @param int    $retry_count Current retry count
     * @return array|WP_Error Response or error
     */
    public function request($endpoint, $method = 'GET', $data = [], $query_params = [], $retry_count = 0) {
        // Build request URL
        $url = $this->api_url . $endpoint;

        // Add query parameters if provided
        if (!empty($query_params)) {
            $url = add_query_arg($query_params, $url);
        }

        // Prepare request arguments
        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 30,
        ];

        // Add body data for non-GET requests
        if ('GET' !== $method && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }

        // Log the request (without sensitive data)
        if ($this->logger) {
            $this->logger->debug(sprintf('API Request: %s %s', $method, $this->maskUrl($url)), [
                'endpoint' => $endpoint,
                'method'   => $method,
            ]);
        }

        // Make the request
        $response = wp_remote_request($url, $args);

        // Check for errors
        if (is_wp_error($response)) {
            if ($this->logger) {
                $this->logger->error('API Request Error: ' . $response->get_error_message(), [
                    'endpoint' => $endpoint,
                    'method'   => $method,
                    'error'    => $response->get_error_message(),
                ]);
            }

            // Check if we should retry
            if ($this->retry_strategy->shouldRetry($retry_count, $response)) {
                $retry_count++;
                $delay = $this->retry_strategy->getDelay($retry_count);
                
                if ($this->logger) {
                    $this->logger->info("Retrying API request after {$delay}s (Attempt {$retry_count})");
                }
                
                // Wait before retrying
                sleep($delay);
                
                return $this->request($endpoint, $method, $data, $query_params, $retry_count);
            }

            return $response;
        }

        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        // Log response
        if ($this->logger) {
            $this->logger->debug(sprintf('API Response: %s %s - %d', $method, $endpoint, $response_code), [
                'response_code' => $response_code,
                'endpoint'      => $endpoint,
            ]);
        }

        // Handle rate limiting
        if (429 === $response_code) {
            if ($this->logger) {
                $this->logger->warning('API Rate Limit Reached', [
                    'endpoint' => $endpoint,
                    'method'   => $method,
                ]);
            }

            // Check if we should retry
            if ($this->retry_strategy->shouldRetry($retry_count)) {
                $retry_count++;
                $delay = $this->retry_strategy->getDelay($retry_count);
                
                // Check if there's a Retry-After header
                $retry_after = wp_remote_retrieve_header($response, 'retry-after');
                if ($retry_after) {
                    $delay = (int) $retry_after;
                }
                
                if ($this->logger) {
                    $this->logger->info("Rate limited. Retrying API request after {$delay}s (Attempt {$retry_count})");
                }
                
                // Wait before retrying
                sleep($delay);
                
                return $this->request($endpoint, $method, $data, $query_params, $retry_count);
            }
        }

        // Handle response based on status code
        if ($response_code >= 400) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';
            
            if ($this->logger) {
                $this->logger->error("API Error: {$error_message}", [
                    'endpoint'      => $endpoint,
                    'method'        => $method,
                    'response_code' => $response_code,
                    'error'         => $error_message,
                ]);
            }

            return new \WP_Error( 
                'printify_api_error', 
                $error_message, 
                [
                    'status' => $response_code,
                    'data'   => $response_data,
                ] 
            );
        }

        return $response_data;
    }

    /**
     * Get shops
     *
     * @return array|WP_Error
     */
    public function getShops() {
        return $this->request('shops');
    }

    /**
     * Get products
     *
     * @param array $params Query parameters
     * @return array|WP_Error
     */
    public function getProducts($params = []) {
        return $this->request("shops/{$this->shop_id}/products", 'GET', [], $params);
    }

    /**
     * Get single product
     *
     * @param string $product_id Printify product ID
     * @return array|WP_Error
     */
    public function getProduct($product_id) {
        return $this->request("shops/{$this->shop_id}/products/{$product_id}");
    }

    /**
     * Create product
     *
     * @param array $data Product data
     * @return array|WP_Error
     */
    public function createProduct($data) {
        return $this->request("shops/{$this->shop_id}/products", 'POST', $data);
    }

    /**
     * Update product
     *
     * @param string $product_id Printify product ID
     * @param array  $data Product data
     * @return array|WP_Error
     */
    public function updateProduct($product_id, $data) {
        return $this->request("shops/{$this->shop_id}/products/{$product_id}", 'PUT', $data);
    }

    /**
     * Delete product
     *
     * @param string $product_id Printify product ID
     * @return array|WP_Error
     */
    public function deleteProduct($product_id) {
        return $this->request("shops/{$this->shop_id}/products/{$product_id}", 'DELETE');
    }

    /**
     * Get orders
     *
     * @param array $params Query parameters
     * @return array|WP_Error
     */
    public function getOrders($params = []) {
        return $this->request("shops/{$this->shop_id}/orders", 'GET', [], $params);
    }

    /**
     * Get single order
     *
     * @param string $order_id Printify order ID
     * @return array|WP_Error
     */
    public function getOrder($order_id) {
        return $this->request("shops/{$this->shop_id}/orders/{$order_id}");
    }

    /**
     * Create order
     *
     * @param array $data Order data
     * @return array|WP_Error
     */
    public function createOrder($data) {
        return $this->request("shops/{$this->shop_id}/orders", 'POST', $data);
    }

    /**
     * Update order
     *
     * @param string $order_id Printify order ID
     * @param array  $data Order data
     * @return array|WP_Error
     */
    public function updateOrder($order_id, $data) {
        return $this->request("shops/{$this->shop_id}/orders/{$order_id}", 'PUT', $data);
    }

    /**
     * Cancel order
     *
     * @param string $order_id Printify order ID
     * @return array|WP_Error
     */
    public function cancelOrder($order_id) {
        return $this->request("shops/{$this->shop_id}/orders/{$order_id}/cancel", 'POST');
    }

    /**
     * Get blueprints (product templates)
     *
     * @return array|WP_Error
     */
    public function getBlueprints() {
        return $this->request('catalog/blueprints');
    }

    /**
     * Get print providers
     *
     * @return array|WP_Error
     */
    public function getPrintProviders() {
        return $this->request('catalog/print-providers');
    }

    /**
     * Get shipping options
     *
     * @return array|WP_Error
     */
    public function getShippingOptions() {
        return $this->request("shops/{$this->shop_id}/shipping");
    }

    /**
     * Mask API key in URL for logging
     *
     * @param string $url URL to mask
     * @return string Masked URL
     */
    private function maskUrl($url) {
        if (empty($this->api_key)) {
            return $url;
        }
        
        // Mask API key
        return str_replace($this->api_key, 'XXXX-XXXX-XXXX-XXXX', $url);
    }
}