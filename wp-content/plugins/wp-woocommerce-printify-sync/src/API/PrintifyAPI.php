<?php
/**
 * Printify API class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

/**
 * PrintifyAPI class.
 */
class PrintifyAPI {
    /**
     * The API base URL.
     *
     * @var string
     */
    private $api_url = 'https://api.printify.com/v1';

    /**
     * The settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * The cache manager instance.
     *
     * @var CacheManager
     */
    private $cache_manager;

    /**
     * The retry count.
     *
     * @var int
     */
    private $retry_count = 0;

    /**
     * The maximum number of retries.
     */
    private const MAX_RETRIES = 3;

    /**
     * Constructor.
     */
    public function __construct(LoggerService $logger, CacheManager $cache_manager) {
        $this->settings = new Settings();
        $this->cache_manager = $cache_manager;
    }

    /**
     * Make a request to the Printify API.
     *
     * @param string $endpoint The API endpoint.
     * @param string $method   The request method.
     * @param array  $data     The request data.
     * @return array|WP_Error
     */
    public function request($endpoint, $method = 'GET', $data = []) {
        // Try cache for GET requests
        if ($method === 'GET') {
            $cache_key = $this->cache_manager->generateKey('api_' . $endpoint, $data ?? []);
            $cached = $this->cache_manager->get($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Make request with retry logic
        while ($this->retry_count < self::MAX_RETRIES) {
            try {
                $response = $this->makeRequest($endpoint, $method, $data);
                
                // Cache successful GET responses
                if ($method === 'GET' && !is_wp_error($response)) {
                    $this->cache_manager->set($cache_key, $response);
                }
                
                return $response;
            } catch (\Exception $e) {
                $this->retry_count++;
                if ($this->retry_count >= self::MAX_RETRIES) {
                    throw $e;
                }
                sleep(pow(2, $this->retry_count)); // Exponential backoff
            }
        }
    }

    /**
     * Make a request to the Printify API.
     *
     * @param string $endpoint The API endpoint.
     * @param string $method   The request method.
     * @param array  $data     The request data.
     * @return array|WP_Error
     */
    private function makeRequest($endpoint, $method = 'GET', $data = []) {
        $api_key = $this->settings->getOption('api_key');
        
        if (empty($api_key)) {
            return new \WP_Error('api_key_missing', __('Printify API key is not set.', 'wp-woocommerce-printify-sync'));
        }
        
        $url = $this->api_url . '/' . ltrim($endpoint, '/');
        
        $args = [
            'method'    => $method,
            'headers'   => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'timeout'   => 30,
        ];
        
        if (!empty($data) && $method !== 'GET') {
            $args['body'] = json_encode($data);
        }
        
        if (!empty($data) && $method === 'GET') {
            $url = add_query_arg($data, $url);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code < 200 || $code >= 300) {
            return new \WP_Error(
                'api_error',
                sprintf(
                    /* translators: %1$s: response code, %2$s: error message */
                    __('API Error: %1$s - %2$s', 'wp-woocommerce-printify-sync'),
                    $code,
                    $body
                )
            );
        }
        
        return json_decode($body, true);
    }

    /**
     * Get shops from Printify.
     *
     * @return array|WP_Error
     */
    public function getShops() {
        return $this->request('shops');
    }

    /**
     * Get products from a shop.
     *
     * @param int   $shop_id The shop ID.
     * @param array $params  The request parameters.
     * @return array|WP_Error
     */
    public function getProducts($shop_id, $params = []) {
        return $this->request("shops/{$shop_id}/products", 'GET', $params);
    }

    /**
     * Get a specific product.
     *
     * @param int $shop_id    The shop ID.
     * @param int $product_id The product ID.
     * @return array|WP_Error
     */
    public function getProduct($shop_id, $product_id) {
        return $this->request("shops/{$shop_id}/products/{$product_id}");
    }

    /**
     * Get product variants.
     *
     * @param int $shop_id    The shop ID.
     * @param int $product_id The product ID.
     * @return array|WP_Error
     */
    public function getProductVariants($shop_id, $product_id) {
        return $this->request("shops/{$shop_id}/products/{$product_id}/variants");
    }

    /**
     * Order endpoints
     */
    public function createOrder(int $shop_id, array $order_data): ?array {
        return $this->request("shops/{$shop_id}/orders.json", 'POST', $order_data);
    }

    public function getOrder(int $shop_id, string $order_id): ?array {
        return $this->request("shops/{$shop_id}/orders/{$order_id}.json");
    }

    public function cancelOrder(int $shop_id, string $order_id): ?array {
        return $this->request("shops/{$shop_id}/orders/{$order_id}/cancel.json", 'POST');
    }

    public function sendOrderToProduction(int $shop_id, string $order_id): ?array {
        return $this->request("shops/{$shop_id}/orders/{$order_id}/send-to-production.json", 'POST');
    }

    /**
     * Return/Refund endpoints
     */
    public function submitReturn(int $shop_id, string $order_id, array $data): ?array {
        return $this->request("shops/{$shop_id}/orders/{$order_id}/returns/submit.json", 'POST', $data);
    }

    public function approveReturn(int $shop_id, string $order_id, string $return_id): ?array {
        return $this->request("shops/{$shop_id}/orders/{$order_id}/returns/{$return_id}/approve.json", 'POST');
    }

    public function rejectReturn(int $shop_id, string $order_id, string $return_id, array $data): ?array {
        return $this->request("shops/{$shop_id}/orders/{$order_id}/returns/{$return_id}/reject.json", 'POST', $data);
    }

    public function getReturnDetails(int $shop_id, string $order_id, string $return_id): ?array {
        return $this->request("shops/{$shop_id}/orders/{$order_id}/returns/{$return_id}.json");
    }

    /**
     * Shipping endpoints 
     */
    public function getShippingInfo(int $shop_id, string $order_id): ?array {
        return $this->request("shops/{$shop_id}/orders/{$order_id}/shipping.json");
    }

    /**
     * Handle webhook payload format 
     */
    private function formatReturnRequest(array $data): array {
        return [
            'items' => $data['items'] ?? [],
            'reason' => $data['reason'] ?? '',
            'comments' => $data['comments'] ?? '',
            'images' => $data['images'] ?? [],
            'return_address' => $data['return_address'] ?? null,
        ];
    }

    /**
     * Test the API connection.
     *
     * @return void
     */
    public function testConnection() {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
        }
        
        $result = $this->getShops();
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success(['message' => __('Connection successful!', 'wp-woocommerce-printify-sync')]);
        }
    }
}
