<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Core\LibraryLoader;
use Exception;

class PrintifyService {
    private $client;
    private $apiKey;
    private $shopId;

    public function __construct() {
        $this->apiKey = get_option('wpwps_printify_api_key', '');
        $this->shopId = get_option('wpwps_printify_shop_id', '');
        $this->client = LibraryLoader::getHttpClient();
    }

    /**
     * Get all shops from Printify
     * 
     * @return array Shops data or empty array on error
     */
    public function getShops(): array {
        try {
            $response = $this->client->request('GET', 'shops.json', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            error_log('PrintifyService: Error getting shops: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all products from Printify
     * 
     * @param int $limit Number of products to retrieve
     * @param int $page Page number
     * @return array Products data or empty array on error
     */
    public function getProducts(int $limit = 10, int $page = 1): array {
        if (empty($this->shopId)) {
            return [];
        }

        try {
            $response = $this->client->request('GET', "shops/{$this->shopId}/products.json", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
                ],
                'query' => [
                    'limit' => $limit,
                    'page' => $page
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            error_log('PrintifyService: Error getting products: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single product from Printify
     *
     * @param string $productId The Printify product ID
     * @return array Product data or empty array on error
     */
    public function getProduct(string $productId): array {
        if (empty($this->shopId)) {
            return [];
        }

        try {
            $response = $this->client->request('GET', "shops/{$this->shopId}/products/{$productId}.json", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            error_log('PrintifyService: Error getting product: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a webhook in Printify
     *
     * @param string $event Event to listen for
     * @param string $url Webhook URL
     * @return array Response data or empty array on error
     */
    public function createWebhook(string $event, string $url): array {
        if (empty($this->shopId)) {
            return [];
        }

        try {
            $response = $this->client->request('POST', "shops/{$this->shopId}/webhooks.json", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
                ],
                'json' => [
                    'event' => $event,
                    'url' => $url
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            error_log('PrintifyService: Error creating webhook: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Test if the API credentials are valid
     *
     * @return bool True if valid, false otherwise
     */
    public function testConnection(): bool {
        try {
            $response = $this->client->request('GET', 'shops.json', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
                ]
            ]);

            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            error_log('PrintifyService: Connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}