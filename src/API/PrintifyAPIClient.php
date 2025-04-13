<?php
/**
 * Printify API Client
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

/**
 * Class PrintifyAPIClient
 * Handles all API interactions with Printify
 */
class PrintifyAPIClient {
    /**
     * API base URL
     *
     * @var string
     */
    private $apiUrl;
    
    /**
     * API key
     *
     * @var string
     */
    private $apiKey;
    
    /**
     * API version
     *
     * @var string
     */
    private $apiVersion = 'v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get API key and URL from settings
        $this->apiKey = get_option('wpwps_printify_api_key', '');
        $this->apiUrl = get_option('wpwps_printify_endpoint', 'https://api.printify.com');
        
        // Remove trailing slash from API URL
        $this->apiUrl = rtrim($this->apiUrl, '/');
    }
    
    /**
     * Get all products from Printify
     *
     * @return array All products
     */
    public function getAllProducts(): array {
        // Get all pages of products
        $allProducts = [];
        $page = 1;
        $perPage = 100;
        
        do {
            $products = $this->getProducts(null, $page, $perPage);
            $allProducts = array_merge($allProducts, $products);
            $page++;
        } while (count($products) === $perPage);
        
        return $allProducts;
    }
    
    /**
     * Get products from Printify
     *
     * @param string|null $shopId Shop ID
     * @param int $page Page number
     * @param int $perPage Products per page
     * @return array Products
     */
    public function getProducts(?string $shopId, int $page = 1, int $perPage = 20): array {
        // If no shop ID, get the default one
        if (!$shopId) {
            $shopId = $this->getDefaultShopId();
        }
        
        // Build endpoint
        $endpoint = "/shops/{$shopId}/products.json";
        
        // Build query parameters
        $queryParams = [
            'page' => $page,
            'limit' => $perPage,
        ];
        
        // Make API request
        $response = $this->makeRequest('GET', $endpoint, $queryParams);
        
        return $response['data'] ?? [];
    }
    
    /**
     * Get products by IDs
     *
     * @param array $productIds Product IDs
     * @return array Products
     */
    public function getProductsByIds(array $productIds): array {
        $products = [];
        
        foreach ($productIds as $id) {
            try {
                $products[] = $this->getProduct($id);
            } catch (\Exception $e) {
                // Log error
                error_log("Error fetching product {$id}: " . $e->getMessage());
            }
        }
        
        return $products;
    }
    
    /**
     * Get a single product
     *
     * @param string $id Product ID
     * @return array Product data
     */
    public function getProduct(string $id): array {
        // Get shop ID
        $shopId = $this->getDefaultShopId();
        
        // Build endpoint
        $endpoint = "/shops/{$shopId}/products/{$id}.json";
        
        // Make API request
        return $this->makeRequest('GET', $endpoint);
    }
    
    /**
     * Get product count
     *
     * @param string|null $shopId Shop ID
     * @return int Product count
     */
    public function getProductCount(?string $shopId): int {
        // If no shop ID, get the default one
        if (!$shopId) {
            $shopId = $this->getDefaultShopId();
        }
        
        // Build endpoint
        $endpoint = "/shops/{$shopId}/products/count.json";
        
        // Make API request
        $response = $this->makeRequest('GET', $endpoint);
        
        return $response['count'] ?? 0;
    }
    
    /**
     * Get shops from Printify
     *
     * @return array Shops
     */
    public function getShops(): array {
        // Build endpoint
        $endpoint = "/shops.json";
        
        // Make API request
        $response = $this->makeRequest('GET', $endpoint);
        
        return $response['data'] ?? [];
    }
    
    /**
     * Get a single order from Printify
     *
     * @param string $id Order ID
     * @return array Order data
     */
    public function getOrder(string $id): array {
        // Get shop ID
        $shopId = $this->getDefaultShopId();
        
        // Build endpoint
        $endpoint = "/shops/{$shopId}/orders/{$id}.json";
        
        // Make API request
        return $this->makeRequest('GET', $endpoint);
    }
    
    /**
     * Get basic order data from Printify
     *
     * @param string $id Order ID
     * @return array Basic order data
     */
    public function getOrderBasicData(string $id): array {
        // Get full order data
        $order = $this->getOrder($id);
        
        // Return basic data
        return [
            'id' => $order['id'],
            'status' => $order['status'],
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at'],
        ];
    }
    
    /**
     * Create an order in Printify
     *
     * @param array $order WooCommerce order data
     * @return string Printify order ID
     */
    public function createOrder(array $order): string {
        // Get shop ID
        $shopId = $this->getDefaultShopId();
        
        // Transform WooCommerce order to Printify format
        $printifyOrder = $this->transformOrderToPrintify($order);
        
        // Build endpoint
        $endpoint = "/shops/{$shopId}/orders.json";
        
        // Make API request
        $response = $this->makeRequest('POST', $endpoint, $printifyOrder);
        
        return $response['id'];
    }
    
    /**
     * Get default shop ID
     *
     * @return string Default shop ID
     */
    private function getDefaultShopId(): string {
        // Get from settings
        $shopId = get_option('wpwps_printify_shop_id', '');
        
        // If not set, get first shop
        if (!$shopId) {
            $shops = $this->getShops();
            if (!empty($shops)) {
                $shopId = $shops[0]['id'];
                update_option('wpwps_printify_shop_id', $shopId);
            } else {
                throw new \Exception('No Printify shop found. Please check your API key.');
            }
        }
        
        return $shopId;
    }
    
    /**
     * Transform WooCommerce order to Printify format
     *
     * @param array $order WooCommerce order
     * @return array Printify order
     */
    private function transformOrderToPrintify(array $order): array {
        // Extract address
        $shipping = $order['shipping'];
        
        // Transform line items
        $lineItems = [];
        foreach ($order['line_items'] as $item) {
            // Get Printify product ID
            $productId = get_post_meta($item['product_id'], '_printify_product_id', true);
            if ($productId) {
                $lineItems[] = [
                    'product_id' => $productId,
                    'variant_id' => $item['variation_id'] ?: $item['product_id'],
                    'quantity' => $item['quantity'],
                ];
            }
        }
        
        // Build Printify order
        return [
            'external_id' => (string) $order['id'],
            'line_items' => $lineItems,
            'shipping_method' => 1, // Standard shipping
            'shipping_address' => [
                'first_name' => $shipping['first_name'],
                'last_name' => $shipping['last_name'],
                'address1' => $shipping['address_1'],
                'address2' => $shipping['address_2'],
                'city' => $shipping['city'],
                'state' => $shipping['state'],
                'zip' => $shipping['postcode'],
                'country' => $shipping['country'],
                'phone' => $order['billing']['phone'],
                'email' => $order['billing']['email'],
            ],
            'send_shipping_notification' => true,
        ];
    }
    
    /**
     * Make request to Printify API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array Response data
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array {
        // Check if API key is set
        if (empty($this->apiKey)) {
            throw new \Exception('Printify API key is not set');
        }
        
        // Build URL
        $url = $this->apiUrl . '/' . $this->apiVersion . $endpoint;
        
        // Add query parameters for GET requests
        if ($method === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        }
        
        // Build request arguments
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        // Add body for non-GET requests
        if ($method !== 'GET' && !empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        // Make request
        $response = wp_remote_request($url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }
        
        // Get response code
        $responseCode = wp_remote_retrieve_response_code($response);
        
        // Check if response is successful
        if ($responseCode < 200 || $responseCode >= 300) {
            $responseBody = wp_remote_retrieve_body($response);
            $error = json_decode($responseBody, true);
            throw new \Exception(
                $error['message'] ?? 'Unknown error from Printify API: ' . $responseCode
            );
        }
        
        // Parse response
        $responseBody = wp_remote_retrieve_body($response);
        $responseData = json_decode($responseBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from Printify API');
        }
        
        return $responseData;
    }
}
