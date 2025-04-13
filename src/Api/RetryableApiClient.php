<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Api;

use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;

/**
 * Wraps the Printify API client with retry logic and rate limiting
 */
class RetryableApiClient implements PrintifyApiInterface {
    /**
     * @var PrintifyApiInterface
     */
    private $api;
    
    /**
     * @var RateLimiterInterface
     */
    private $rate_limiter;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * Constructor
     *
     * @param PrintifyApiInterface $api Base API client
     * @param RateLimiterInterface $rate_limiter Rate limiter
     * @param LoggerInterface $logger Logger
     */
    public function __construct(
        PrintifyApiInterface $api,
        RateLimiterInterface $rate_limiter,
        LoggerInterface $logger
    ) {
        $this->api = $api;
        $this->rate_limiter = $rate_limiter;
        $this->logger = $logger;
    }
    
    /**
     * Make an API request with retry logic
     *
     * @param string $method HTTP method
     * @param array $args Arguments to pass to the method
     * @param int $retry_count Current retry count
     * @return mixed|WP_Error API response or error
     */
    private function makeRequestWithRetry($method, $args = [], $retry_count = 0) {
        // The first argument is typically the endpoint
        $endpoint = isset($args[0]) ? $args[0] : 'unknown';
        
        // Check if we should rate limit this request
        if ($this->rate_limiter->shouldLimit($endpoint)) {
            $delay = $this->rate_limiter->getRetryDelay($endpoint);
            
            if ($retry_count >= 5) {
                return new \WP_Error(
                    'rate_limited',
                    sprintf('Rate limit reached for %s after %d retries', $endpoint, $retry_count)
                );
            }
            
            $this->logger->log_info(
                'api_retry',
                sprintf('Rate limited, waiting %d seconds before retry #%d for %s', 
                    $delay, $retry_count + 1, $endpoint)
            );
            
            // Wait and retry
            sleep($delay);
            return $this->makeRequestWithRetry($method, $args, $retry_count + 1);
        }
        
        try {
            // Make the actual API call by forwarding to base client
            $response = call_user_func_array([$this->api, $method], $args);
            
            // Handle errors
            if (is_wp_error($response)) {
                $code = $response->get_error_code();
                $message = $response->get_error_message();
                $status = 0;
                
                // Extract status code if available
                $error_data = $response->get_error_data();
                if (is_array($error_data) && isset($error_data['status'])) {
                    $status = $error_data['status'];
                }
                
                // Record the failure
                $this->rate_limiter->recordFailure($endpoint, $message, $status);
                
                // Check if we should retry
                if ($retry_count < 5) {
                    $delay = $this->rate_limiter->getRetryDelay($endpoint);
                    
                    if ($delay > 0) {
                        $this->logger->log_info(
                            'api_retry',
                            sprintf('API error: %s. Retrying in %d seconds (attempt %d/5)', 
                                $message, $delay, $retry_count + 1)
                        );
                        
                        sleep($delay);
                        return $this->makeRequestWithRetry($method, $args, $retry_count + 1);
                    }
                }
                
                return $response; // Return the error if no more retries
            }
            
            // Success - record it
            $headers = [];
            if (is_array($response) && isset($response['headers'])) {
                $headers = $response['headers'];
            }
            
            $this->rate_limiter->recordSuccess($endpoint, $headers);
            return $response;
            
        } catch (\Exception $e) {
            // Unexpected exception
            $this->logger->log_error(
                'api_retry',
                sprintf('API exception for %s: %s', $endpoint, $e->getMessage())
            );
            
            $this->rate_limiter->recordFailure($endpoint, $e->getMessage());
            
            return new \WP_Error('api_exception', $e->getMessage());
        }
    }
    
    /**
     * Implement all methods from PrintifyApiInterface
     * This is a proxy pattern that adds retry functionality
     */
    
    /**
     * Get products with retry logic
     */
    public function get_products($shop_id, $args = []) {
        return $this->makeRequestWithRetry('get_products', [$shop_id, $args]);
    }
    
    /**
     * Get product with retry logic
     */
    public function get_product($shop_id, $product_id) {
        return $this->makeRequestWithRetry('get_product', [$shop_id, $product_id]);
    }
    
    /**
     * Get product variants with retry logic
     */
    public function get_product_variants($shop_id, $product_id) {
        return $this->makeRequestWithRetry('get_product_variants', [$shop_id, $product_id]);
    }
    
    /**
     * Create product with retry logic
     */
    public function create_product($shop_id, $product_data) {
        return $this->makeRequestWithRetry('create_product', [$shop_id, $product_data]);
    }
    
    /**
     * Update product with retry logic
     */
    public function update_product($shop_id, $product_id, $product_data) {
        return $this->makeRequestWithRetry('update_product', [$shop_id, $product_id, $product_data]);
    }
    
    /**
     * Delete product with retry logic
     */
    public function delete_product($shop_id, $product_id) {
        return $this->makeRequestWithRetry('delete_product', [$shop_id, $product_id]);
    }
    
    /**
     * Get orders with retry logic
     */
    public function get_orders($shop_id, $args = []) {
        return $this->makeRequestWithRetry('get_orders', [$shop_id, $args]);
    }
    
    /**
     * Get order with retry logic
     */
    public function get_order($shop_id, $order_id) {
        return $this->makeRequestWithRetry('get_order', [$shop_id, $order_id]);
    }
    
    /**
     * Send order with retry logic
     */
    public function send_order($shop_id, $order_data) {
        return $this->makeRequestWithRetry('send_order', [$shop_id, $order_data]);
    }
    
    /**
     * Cancel order with retry logic
     */
    public function cancel_order($shop_id, $order_id) {
        return $this->makeRequestWithRetry('cancel_order', [$shop_id, $order_id]);
    }
    
    /**
     * Generic GET request with retry logic
     */
    public function get($endpoint, $args = []) {
        return $this->makeRequestWithRetry('get', [$endpoint, $args]);
    }
    
    /**
     * Generic POST request with retry logic
     */
    public function post($endpoint, $args = []) {
        return $this->makeRequestWithRetry('post', [$endpoint, $args]);
    }
    
    /**
     * Generic PUT request with retry logic
     */
    public function put($endpoint, $args = []) {
        return $this->makeRequestWithRetry('put', [$endpoint, $args]);
    }
    
    /**
     * Generic DELETE request with retry logic
     */
    public function delete($endpoint, $args = []) {
        return $this->makeRequestWithRetry('delete', [$endpoint, $args]);
    }
}
