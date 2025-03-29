<?php
namespace WPWPS\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use WPWPS\Helpers\EncryptionHelper;

/**
 * Service for communicating with the Printify API
 * Based on official documentation: https://developers.printify.com/
 */
class PrintifyApiService {
    /**
     * @var Client The GuzzleHttp client
     */
    private $client;
    
    /**
     * @var string The base URL for Printify API
     */
    private $apiUrl = 'https://api.printify.com/v1/';
    
    /**
     * @var string The API key
     */
    private $apiKey;

    /**
     * @var string The shop ID
     */
    private $shopId;

    /**
     * PrintifyApiService constructor
     *
     * @param string|null $apiKey Optional API key
     */
    public function __construct(?string $apiKey = null) {
        // Get API key from options if not provided
        if ($apiKey === null) {
            $encryptedKey = get_option('wpwps_printify_api_key', '');
            $this->apiKey = EncryptionHelper::decrypt($encryptedKey);
        } else {
            $this->apiKey = $apiKey;
        }
        
        // Get shop ID from options
        $this->shopId = get_option('wpwps_printify_shop_id', '');
        
        // Get API endpoint from options or use default
        $this->apiUrl = get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1/');
        
        // Initialize the HTTP client
        $this->initClient();
    }
    
    /**
     * Initialize the HTTP client with current settings
     *
     * @return void
     */
    private function initClient(): void {
        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$this->apiKey}",
            ],
            'timeout' => 30,
            'verify' => true, // Verify SSL certificates
            'http_errors' => false, // Don't throw exceptions for 4xx/5xx responses
        ]);
    }
    
    /**
     * Set the API key
     *
     * @param string $apiKey The API key to set
     * @return void
     */
    public function setApiKey(string $apiKey): void {
        $this->apiKey = $apiKey;
        $this->initClient();
    }

    /**
     * Set the shop ID
     *
     * @param string $shopId The shop ID to set
     * @return void
     */
    public function setShopId(string $shopId): void {
        $this->shopId = $shopId;
    }

    /**
     * Get the current shop ID
     *
     * @return string
     */
    public function getShopId(): string {
        return $this->shopId;
    }

    /**
     * Check if all necessary credentials are set
     * 
     * @return bool
     */
    public function isConfigured(): bool {
        return !empty($this->apiKey) && !empty($this->shopId);
    }

    /**
     * Make a GET request to the API
     * 
     * @param string $endpoint API endpoint
     * @param array $query Optional query parameters
     * @return array|null Response data or null on error
     */
    private function get(string $endpoint, array $query = []): ?array {
        try {
            $options = [];
            if (!empty($query)) {
                $options['query'] = $query;
            }

            $response = $this->client->get($endpoint, $options);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return json_decode($response->getBody()->getContents(), true);
            }
            
            $this->logError("GET {$endpoint} failed with status code {$statusCode}", null);
            return null;
        } catch (GuzzleException $e) {
            $this->logError("GET {$endpoint} request failed", $e);
            return null;
        }
    }

    /**
     * Make a POST request to the API
     * 
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array|null Response data or null on error
     */
    private function post(string $endpoint, array $data = []): ?array {
        try {
            $options = [];
            if (!empty($data)) {
                $options[RequestOptions::JSON] = $data;
            }

            $response = $this->client->post($endpoint, $options);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return json_decode($response->getBody()->getContents(), true);
            }
            
            $this->logError("POST {$endpoint} failed with status code {$statusCode}", null);
            return null;
        } catch (GuzzleException $e) {
            $this->logError("POST {$endpoint} request failed", $e);
            return null;
        }
    }

    /**
     * Make a PUT request to the API
     * 
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array|null Response data or null on error
     */
    private function put(string $endpoint, array $data = []): ?array {
        try {
            $options = [];
            if (!empty($data)) {
                $options[RequestOptions::JSON] = $data;
            }

            $response = $this->client->put($endpoint, $options);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return json_decode($response->getBody()->getContents(), true);
            }
            
            $this->logError("PUT {$endpoint} failed with status code {$statusCode}", null);
            return null;
        } catch (GuzzleException $e) {
            $this->logError("PUT {$endpoint} request failed", $e);
            return null;
        }
    }

    /**
     * Make a DELETE request to the API
     * 
     * @param string $endpoint API endpoint
     * @return bool Success status
     */
    private function delete(string $endpoint): bool {
        try {
            $response = $this->client->delete($endpoint);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return true;
            }
            
            $this->logError("DELETE {$endpoint} failed with status code {$statusCode}", null);
            return false;
        } catch (GuzzleException $e) {
            $this->logError("DELETE {$endpoint} request failed", $e);
            return false;
        }
    }

    /**
     * Get all shops
     * 
     * @return array|null List of shops or null on error
     */
    public function getShops(): ?array {
        return $this->get('shops.json');
    }

    /**
     * Get a specific shop by ID
     * 
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Shop data or null on error
     */
    public function getShop(?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot get shop: Shop ID is not set", null);
            return null;
        }

        return $this->get("shops/{$shopId}.json");
    }

    /**
     * Get all products for a shop
     * 
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @param int $page Page number (starting from 1)
     * @param int $limit Items per page
     * @return array|null Products array or null on error
     */
    public function getProducts(?string $shopId = null, int $page = 1, int $limit = 10): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot get products: Shop ID is not set", null);
            return null;
        }

        return $this->get("shops/{$shopId}/products.json", [
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * Get a specific product by ID
     * 
     * @param string $productId Product ID
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Product data or null on error
     */
    public function getProduct(string $productId, ?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot get product: Shop ID is not set", null);
            return null;
        }

        return $this->get("shops/{$shopId}/products/{$productId}.json");
    }

    /**
     * Create a new product
     * 
     * @param array $productData Product data
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Created product data or null on error
     */
    public function createProduct(array $productData, ?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot create product: Shop ID is not set", null);
            return null;
        }

        return $this->post("shops/{$shopId}/products.json", $productData);
    }

    /**
     * Update an existing product
     * 
     * @param string $productId Product ID
     * @param array $productData Updated product data
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Updated product data or null on error
     */
    public function updateProduct(string $productId, array $productData, ?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot update product: Shop ID is not set", null);
            return null;
        }

        return $this->put("shops/{$shopId}/products/{$productId}.json", $productData);
    }

    /**
     * Delete a product
     * 
     * @param string $productId Product ID
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return bool Success status
     */
    public function deleteProduct(string $productId, ?string $shopId = null): bool {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot delete product: Shop ID is not set", null);
            return false;
        }

        return $this->delete("shops/{$shopId}/products/{$productId}.json");
    }

    /**
     * Publish a product to a connected store
     * 
     * @param string $productId Product ID
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Publish result data or null on error
     */
    public function publishProduct(string $productId, ?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot publish product: Shop ID is not set", null);
            return null;
        }

        return $this->post("shops/{$shopId}/products/{$productId}/publish.json");
    }

    /**
     * Unpublish a product from a connected store
     * 
     * @param string $productId Product ID
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Unpublish result data or null on error
     */
    public function unpublishProduct(string $productId, ?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot unpublish product: Shop ID is not set", null);
            return null;
        }

        return $this->post("shops/{$shopId}/products/{$productId}/unpublish.json");
    }

    /**
     * Get all orders for a shop
     * 
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @param int $page Page number (starting from 1)
     * @param int $limit Items per page
     * @param string|null $status Filter by status
     * @return array|null Orders array or null on error
     */
    public function getOrders(?string $shopId = null, int $page = 1, int $limit = 10, ?string $status = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot get orders: Shop ID is not set", null);
            return null;
        }

        $query = [
            'page' => $page,
            'limit' => $limit
        ];

        if ($status !== null) {
            $query['status'] = $status;
        }

        return $this->get("shops/{$shopId}/orders.json", $query);
    }

    /**
     * Get a specific order by ID
     * 
     * @param string $orderId Order ID
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Order data or null on error
     */
    public function getOrder(string $orderId, ?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot get order: Shop ID is not set", null);
            return null;
        }

        return $this->get("shops/{$shopId}/orders/{$orderId}.json");
    }

    /**
     * Create a new order
     * 
     * @param array $orderData Order data
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Created order data or null on error
     */
    public function createOrder(array $orderData, ?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot create order: Shop ID is not set", null);
            return null;
        }

        return $this->post("shops/{$shopId}/orders.json", $orderData);
    }

    /**
     * Calculate shipping costs for an order
     * 
     * @param array $shippingData Shipping calculation data
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Shipping costs or null on error
     */
    public function calculateShipping(array $shippingData, ?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot calculate shipping: Shop ID is not set", null);
            return null;
        }

        return $this->post("shops/{$shopId}/orders/shipping.json", $shippingData);
    }

    /**
     * Cancel an order
     * 
     * @param string $orderId Order ID
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Cancel result data or null on error
     */
    public function cancelOrder(string $orderId, ?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot cancel order: Shop ID is not set", null);
            return null;
        }

        return $this->post("shops/{$shopId}/orders/{$orderId}/cancel.json");
    }

    /**
     * Send an order for fulfillment
     * 
     * @param string $orderId Order ID
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Fulfill result data or null on error
     */
    public function fulfillOrder(string $orderId, ?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot fulfill order: Shop ID is not set", null);
            return null;
        }

        return $this->post("shops/{$shopId}/orders/{$orderId}/send_to_production.json");
    }

    /**
     * Get all print providers
     * 
     * @return array|null Print providers or null on error
     */
    public function getPrintProviders(): ?array {
        return $this->get('catalog/print_providers.json');
    }

    /**
     * Get a specific print provider by ID
     * 
     * @param string $providerId Provider ID
     * @return array|null Provider data or null on error
     */
    public function getPrintProvider(string $providerId): ?array {
        return $this->get("catalog/print_providers/{$providerId}.json");
    }

    /**
     * Get all blueprints (product types) for a print provider
     * 
     * @param string $providerId Provider ID
     * @return array|null Blueprints or null on error
     */
    public function getBlueprints(string $providerId): ?array {
        return $this->get("catalog/print_providers/{$providerId}/blueprints.json");
    }

    /**
     * Get a specific blueprint (product type) by ID
     * 
     * @param string $providerId Provider ID
     * @param string $blueprintId Blueprint ID
     * @return array|null Blueprint data or null on error
     */
    public function getBlueprint(string $providerId, string $blueprintId): ?array {
        return $this->get("catalog/print_providers/{$providerId}/blueprints/{$blueprintId}.json");
    }

    /**
     * Get all variants for a blueprint
     * 
     * @param string $providerId Provider ID
     * @param string $blueprintId Blueprint ID
     * @return array|null Variants or null on error
     */
    public function getVariants(string $providerId, string $blueprintId): ?array {
        return $this->get("catalog/print_providers/{$providerId}/blueprints/{$blueprintId}/variants.json");
    }

    /**
     * Get print areas for a blueprint
     * 
     * @param string $providerId Provider ID
     * @param string $blueprintId Blueprint ID
     * @return array|null Print areas or null on error
     */
    public function getPrintAreas(string $providerId, string $blueprintId): ?array {
        return $this->get("catalog/print_providers/{$providerId}/blueprints/{$blueprintId}/print_areas.json");
    }

    /**
     * Get shipping information for a blueprint
     * 
     * @param string $providerId Provider ID
     * @param string $blueprintId Blueprint ID
     * @return array|null Shipping info or null on error
     */
    public function getShippingInfo(string $providerId, string $blueprintId): ?array {
        return $this->get("catalog/print_providers/{$providerId}/blueprints/{$blueprintId}/shipping.json");
    }

    /**
     * Get all webhooks for a shop
     * 
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Webhooks or null on error
     */
    public function getWebhooks(?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot get webhooks: Shop ID is not set", null);
            return null;
        }

        return $this->get("shops/{$shopId}/webhooks.json");
    }

    /**
     * Create a new webhook
     * 
     * @param string $topic Webhook topic
     * @param string $url Webhook URL
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return array|null Created webhook data or null on error
     */
    public function createWebhook(string $topic, string $url, ?string $shopId = null): ?array {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot create webhook: Shop ID is not set", null);
            return null;
        }

        return $this->post("shops/{$shopId}/webhooks.json", [
            'topic' => $topic,
            'url' => $url
        ]);
    }

    /**
     * Delete a webhook
     * 
     * @param string $webhookId Webhook ID
     * @param string|null $shopId Optional shop ID (uses default if not provided)
     * @return bool Success status
     */
    public function deleteWebhook(string $webhookId, ?string $shopId = null): bool {
        $shopId = $shopId ?: $this->shopId;
        if (empty($shopId)) {
            $this->logError("Cannot delete webhook: Shop ID is not set", null);
            return false;
        }

        return $this->delete("shops/{$shopId}/webhooks/{$webhookId}.json");
    }

    /**
     * Log an error message
     * 
     * @param string $message Error message
     * @param GuzzleException|null $exception Optional exception
     * @return void
     */
    private function logError(string $message, ?GuzzleException $exception): void {
        $errorMessage = 'Printify API Error: ' . $message;
        
        if ($exception !== null) {
            $errorMessage .= ' - ' . $exception->getMessage() . ' (Code: ' . $exception->getCode() . ')';
        }
        
        error_log($errorMessage);
    }

    /**
     * Check if the current API key is valid
     *
     * @return bool True if valid, false otherwise
     */
    public function isApiKeyValid(): bool {
        try {
            $response = $this->client->get('shops.json');
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }
}