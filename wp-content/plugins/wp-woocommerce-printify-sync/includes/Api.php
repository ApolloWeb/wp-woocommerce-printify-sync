<?php

namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Printify API Client Class
 */
class Api
{
    /**
     * Printify API Key
     * 
     * @var string
     */
    private $apiKey;

    /**
     * Constructor
     * 
     * @param string $apiKey Printify API key
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Get all shops for the authenticated user
     * 
     * @return array|WP_Error Array of shops or WP_Error on failure
     */
    public function getShops()
    {
        $endpoint = 'https://api.printify.com/v1/shops.json';
        $response = $this->makeRequest($endpoint);
        
        return $response;
    }

    /**
     * Get details for a specific shop
     * 
     * @param string $shopId The shop ID
     * @return array|WP_Error Shop details or WP_Error on failure
     */
    public function getShopDetails($shopId)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}.json";
        return $this->makeRequest($endpoint);
    }

    /**
     * Get products
     * 
     * @param string $shopId The shop ID
     * @param int $limit Number of products to retrieve (default to what the API uses)
     * @return array|WP_Error List of products or WP_Error on failure
     */
    public function getProducts($shopId, $limit = 10)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/products.json?limit={$limit}";
        return $this->makeRequest($endpoint);
    }

    /**
     * Get a specific product
     * 
     * @param string $shopId The shop ID
     * @param string $productId The product ID
     * @return array|WP_Error Product details or WP_Error on failure
     */
    public function getProduct($shopId, $productId)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/products/{$productId}.json";
        return $this->makeRequest($endpoint);
    }

    /**
     * Create a new product
     * 
     * @param string $shopId The shop ID
     * @param array $productData Product data to create
     * @return array|WP_Error Created product or WP_Error on failure
     */
    public function createProduct($shopId, $productData)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/products.json";
        return $this->makeRequest($endpoint, 'POST', $productData);
    }

    /**
     * Update an existing product
     * 
     * @param string $shopId The shop ID
     * @param string $productId The product ID
     * @param array $productData Product data to update
     * @return array|WP_Error Updated product or WP_Error on failure
     */
    public function updateProduct($shopId, $productId, $productData)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/products/{$productId}.json";
        return $this->makeRequest($endpoint, 'PUT', $productData);
    }

    /**
     * Delete a product
     * 
     * @param string $shopId The shop ID
     * @param string $productId The product ID
     * @return array|WP_Error Result or WP_Error on failure
     */
    public function deleteProduct($shopId, $productId)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/products/{$productId}.json";
        return $this->makeRequest($endpoint, 'DELETE');
    }

    /**
     * Publish a product to a sales channel
     * 
     * @param string $shopId The shop ID
     * @param string $productId The product ID
     * @param array $publishData Publish data
     * @return array|WP_Error Result or WP_Error on failure
     */
    public function publishProduct($shopId, $productId, $publishData)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/products/{$productId}/publish.json";
        return $this->makeRequest($endpoint, 'POST', $publishData);
    }

    /**
     * Get product variants
     * 
     * @param string $shopId The shop ID
     * @param string $productId The product ID
     * @return array|WP_Error Product variants or WP_Error on failure
     */
    public function getProductVariants($shopId, $productId)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/products/{$productId}/variants.json";
        return $this->makeRequest($endpoint);
    }

    /**
     * Get orders
     * 
     * @param string $shopId The shop ID
     * @return array|WP_Error Orders or WP_Error on failure
     */
    public function getOrders($shopId)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/orders.json";
        return $this->makeRequest($endpoint);
    }

    /**
     * Create an order
     * 
     * @param string $shopId The shop ID
     * @param array $orderData Order data to create
     * @return array|WP_Error Created order or WP_Error on failure
     */
    public function createOrder($shopId, $orderData)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/orders.json";
        return $this->makeRequest($endpoint, 'POST', $orderData);
    }

    /**
     * Update an existing order
     * 
     * @param string $shopId The shop ID
     * @param string $orderId The order ID
     * @param array $orderData Order data to update
     * @return array|WP_Error Updated order or WP_Error on failure
     */
    public function updateOrder($shopId, $orderId, $orderData)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/orders/{$orderId}.json";
        return $this->makeRequest($endpoint, 'PUT', $orderData);
    }

    /**
     * Delete an order
     * 
     * @param string $shopId The shop ID
     * @param string $orderId The order ID
     * @return array|WP_Error Result or WP_Error on failure
     */
    public function deleteOrder($shopId, $orderId)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/orders/{$orderId}.json";
        return $this->makeRequest($endpoint, 'DELETE');
    }

    /**
     * Get all webhooks for a shop
     * 
     * @param string $shopId The shop ID
     * @return array|WP_Error List of webhooks or WP_Error on failure
     */
    public function getWebhooks($shopId) 
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/webhooks.json";
        return $this->makeRequest($endpoint);
    }

    /**
     * Create a new webhook
     * 
     * @param string $shopId The shop ID
     * @param array $webhookData Webhook data
     * @return array|WP_Error Created webhook or WP_Error on failure
     */
    public function createWebhook($shopId, $webhookData)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/webhooks.json";
        return $this->makeRequest($endpoint, 'POST', $webhookData);
    }

    /**
     * Delete a webhook
     * 
     * @param string $shopId The shop ID
     * @param string $webhookId The webhook ID
     * @return array|WP_Error Result or WP_Error on failure
     */
    public function deleteWebhook($shopId, $webhookId)
    {
        $endpoint = "https://api.printify.com/v1/shops/{$shopId}/webhooks/{$webhookId}.json";
        return $this->makeRequest($endpoint, 'DELETE');
    }

    /**
     * Get print providers
     * 
     * @return array|WP_Error List of print providers or WP_Error on failure
     */
    public function getPrintProviders()
    {
        $endpoint = "https://api.printify.com/v1/catalog/print_providers.json";
        return $this->makeRequest($endpoint);
    }

    /**
     * Get print provider products
     * 
     * @param string $providerId The print provider ID
     * @return array|WP_Error List of products or WP_Error on failure
     */
    public function getPrintProviderProducts($providerId)
    {
        $endpoint = "https://api.printify.com/v1/catalog/print_providers/{$providerId}/products.json";
        return $this->makeRequest($endpoint);
    }

    /**
     * Make API request to Printify
     * 
     * @param string $endpoint API endpoint
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param array|null $body Request body for POST/PUT
     * @return array|WP_Error Response data or WP_Error on failure
     */
    private function makeRequest($endpoint, $method = 'GET', $body = null)
    {
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'timeout' => 60,
            'method' => $method,
        ];

        if ($body) {
            $args['body'] = json_encode($body);
            $args['headers']['Content-Type'] = 'application/json';
        }

        $response = wp_remote_request($endpoint, $args);
        if (is_wp_error($response)) {
            error_log('Printify API Error: ' . $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['message']) ? $error_data['message'] : 'Unexpected response from Printify API';
            error_log('Printify API Error (' . $code . '): ' . $error_message);
            return new \WP_Error('printify_api_error', $error_message, [
                'code' => $code,
                'response' => $error_data
            ]);
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Printify API Error: JSON decoding error ' . json_last_error_msg());
            return new \WP_Error('json_error', __('Error decoding Printify API response', 'wp-woocommerce-printify-sync'), [
                'json_error' => json_last_error_msg()
            ]);
        }

        return $data;
    }
}