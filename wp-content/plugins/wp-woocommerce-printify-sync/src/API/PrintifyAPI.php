<?php
/**
 * Printify API handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Printify API class for making requests to the Printify API.
 */
class PrintifyAPI {
    /**
     * API version.
     *
     * @var string
     */
    private $api_version = 'v1';

    /**
     * API base URL.
     *
     * @var string
     */
    private $api_base_url = 'https://api.printify.com/';

    /**
     * API endpoint.
     *
     * @var string
     */
    private $api_endpoint;

    /**
     * API key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Current shop ID.
     *
     * @var string
     */
    private $shop_id;

    /**
     * Rate limit remaining.
     *
     * @var int
     */
    private $rate_limit_remaining = 100;

    /**
     * Rate limit reset timestamp.
     *
     * @var int
     */
    private $rate_limit_reset = 0;

    /**
     * Constructor.
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->loadSettings();
    }

    /**
     * Load API settings.
     *
     * @return void
     */
    private function loadSettings() {
        $settings = get_option('wpwps_settings', []);
        
        $this->api_key = isset($settings['api_key']) ? $this->decryptApiKey($settings['api_key']) : '';
        $this->api_endpoint = isset($settings['api_endpoint']) ? $settings['api_endpoint'] : $this->api_base_url . $this->api_version;
        $this->shop_id = isset($settings['shop_id']) ? $settings['shop_id'] : '';
    }

    /**
     * Encrypt API key.
     *
     * @param string $api_key API key to encrypt.
     * @return string
     */
    public function encryptApiKey($api_key) {
        if (empty($api_key)) {
            return '';
        }
        
        $method = 'aes-256-cbc';
        $key = substr(SECURE_AUTH_KEY, 0, 32);
        $iv = substr(SECURE_AUTH_SALT, 0, 16);
        
        $encrypted = openssl_encrypt($api_key, $method, $key, 0, $iv);
        
        return base64_encode($encrypted);
    }

    /**
     * Decrypt API key.
     *
     * @param string $encrypted_api_key Encrypted API key.
     * @return string
     */
    public function decryptApiKey($encrypted_api_key) {
        if (empty($encrypted_api_key)) {
            return '';
        }
        
        $method = 'aes-256-cbc';
        $key = substr(SECURE_AUTH_KEY, 0, 32);
        $iv = substr(SECURE_AUTH_SALT, 0, 16);
        
        $decrypted = openssl_decrypt(base64_decode($encrypted_api_key), $method, $key, 0, $iv);
        
        return $decrypted;
    }

    /**
     * Make an API request.
     *
     * @param string $endpoint API endpoint.
     * @param string $method   HTTP method.
     * @param array  $data     Request data.
     * @return array|WP_Error
     */
    public function request($endpoint, $method = 'GET', $data = []) {
        // Check API key.
        if (empty($this->api_key)) {
            return new \WP_Error('missing_api_key', __('API key is missing.', 'wp-woocommerce-printify-sync'));
        }
        
        // Check rate limit.
        if ($this->rate_limit_remaining <= 0 && time() < $this->rate_limit_reset) {
            $wait_time = $this->rate_limit_reset - time();
            return new \WP_Error(
                'rate_limit_exceeded',
                sprintf(__('API rate limit exceeded. Try again in %d seconds.', 'wp-woocommerce-printify-sync'), $wait_time)
            );
        }
        
        // Prepare request URL.
        $url = $this->api_endpoint . '/' . ltrim($endpoint, '/');
        
        // Prepare request arguments.
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        // Add request body for non-GET requests.
        if ('GET' !== $method && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        } elseif ('GET' === $method && !empty($data)) {
            // Add query parameters for GET requests.
            $url = add_query_arg($data, $url);
        }
        
        // Log request.
        $this->logger->log('API Request', [
            'endpoint' => $endpoint,
            'method' => $method,
            'data' => $data,
        ]);
        
        // Make request.
        $response = wp_remote_request($url, $args);
        
        // Check for errors.
        if (is_wp_error($response)) {
            $this->logger->log('API Error', [
                'endpoint' => $endpoint,
                'method' => $method,
                'data' => $data,
                'error' => $response->get_error_message(),
            ]);
            
            return $response;
        }
        
        // Get response code.
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Update rate limit info.
        $this->rate_limit_remaining = (int) wp_remote_retrieve_header($response, 'x-ratelimit-remaining');
        $this->rate_limit_reset = (int) wp_remote_retrieve_header($response, 'x-ratelimit-reset');
        
        // Get response body.
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Log response.
        $this->logger->log('API Response', [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $response_code,
            'data' => $data,
        ]);
        
        // Check for API errors.
        if ($response_code >= 400) {
            return new \WP_Error(
                'api_error',
                isset($data['message']) ? $data['message'] : __('Unknown API error.', 'wp-woocommerce-printify-sync'),
                [
                    'status' => $response_code,
                    'response' => $data,
                ]
            );
        }
        
        return $data;
    }

    /**
     * Get shops.
     *
     * @return array|WP_Error
     */
    public function getShops() {
        return $this->request('shops.json');
    }

    /**
     * Get shop.
     *
     * @param string $shop_id Shop ID.
     * @return array|WP_Error
     */
    public function getShop($shop_id = '') {
        $shop_id = $shop_id ?: $this->shop_id;
        
        if (empty($shop_id)) {
            return new \WP_Error('missing_shop_id', __('Shop ID is missing.', 'wp-woocommerce-printify-sync'));
        }
        
        return $this->request("shops/{$shop_id}.json");
    }

    /**
     * Get products.
     *
     * @param int $limit  Number of products to return.
     * @param int $page   Page number.
     * @return array|WP_Error
     */
    public function getProducts($limit = 20, $page = 1) {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', __('Shop ID is missing.', 'wp-woocommerce-printify-sync'));
        }
        
        return $this->request("shops/{$this->shop_id}/products.json", 'GET', [
            'limit' => $limit,
            'page' => $page,
        ]);
    }

    /**
     * Get product.
     *
     * @param string $product_id Product ID.
     * @return array|WP_Error
     */
    public function getProduct($product_id) {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', __('Shop ID is missing.', 'wp-woocommerce-printify-sync'));
        }
        
        return $this->request("shops/{$this->shop_id}/products/{$product_id}.json");
    }

    /**
     * Get orders.
     *
     * @param int $limit Number of orders to return.
     * @param int $page  Page number.
     * @return array|WP_Error
     */
    public function getOrders($limit = 20, $page = 1) {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', __('Shop ID is missing.', 'wp-woocommerce-printify-sync'));
        }
        
        return $this->request("shops/{$this->shop_id}/orders.json", 'GET', [
            'limit' => $limit,
            'page' => $page,
        ]);
    }

    /**
     * Get order.
     *
     * @param string $order_id Order ID.
     * @return array|WP_Error
     */
    public function getOrder($order_id) {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', __('Shop ID is missing.', 'wp-woocommerce-printify-sync'));
        }
        
        return $this->request("shops/{$this->shop_id}/orders/{$order_id}.json");
    }

    /**
     * Create order.
     *
     * @param array $order_data Order data.
     * @return array|WP_Error
     */
    public function createOrder($order_data) {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', __('Shop ID is missing.', 'wp-woocommerce-printify-sync'));
        }
        
        return $this->request("shops/{$this->shop_id}/orders.json", 'POST', $order_data);
    }

    /**
     * Get shipping methods.
     *
     * @return array|WP_Error
     */
    public function getShippingMethods() {
        if (empty($this->shop_id)) {
            return new \WP_Error('missing_shop_id', __('Shop ID is missing.', 'wp-woocommerce-printify-sync'));
        }
        
        return $this->request("shops/{$this->shop_id}/shipping_methods.json");
    }

    /**
     * Test connection.
     *
     * @return bool|WP_Error
     */
    public function testConnection() {
        $response = $this->getShops();
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return true;
    }
}
