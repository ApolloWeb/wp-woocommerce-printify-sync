<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\API\Contracts\PrintifyAPIInterface;
use ApolloWeb\WPWooCommercePrintifySync\Http\Contracts\HttpClientInterface;

class PrintifyAPI implements PrintifyAPIInterface {
    private HttpClientInterface $client;
    
    public function __construct(HttpClientInterface $client) {
        $this->client = $client;
    }

    private function getApiKey(): string {
        $api_key = get_option('printify_api_key', '');
        if (empty($api_key)) {
            throw new \Exception(__('API key is not configured', 'wp-woocommerce-printify-sync'));
        }
        return $api_key;
    }

    private function getBaseUrl(): string {
        return 'https://api.printify.com/v1';  // Hardcode correct API endpoint
    }

    public function getShops(): array {
        try {
            $response = $this->request('shops.json');
            
            // Log the response for debugging
            error_log('PrintifyAPI getShops response: ' . print_r($response, true));
            
            // Handle different response structures
            if (isset($response['data']) && is_array($response['data'])) {
                return $response['data'];
            }
            
            if (is_array($response) && isset($response[0])) {
                return $response;
            }
            
            // If we get here, log the unexpected structure
            error_log('Unexpected Printify API response structure: ' . print_r($response, true));
            return [];
            
        } catch (\Exception $e) {
            error_log('PrintifyAPI getShops error: ' . $e->getMessage());
            throw new \Exception(
                sprintf('Failed to get shops: %s', $e->getMessage()),
                $e->getCode()
            );
        }
    }

    public function getProducts(string $shop_id): array {
        try {
            error_log('Getting products for shop ID: ' . $shop_id);

            // Validate shop_id format
            if (!preg_match('/^\d+$/', $shop_id)) {
                error_log('Invalid shop ID format: ' . $shop_id);
                throw new \Exception('Invalid shop ID format');
            }

            $endpoint = "shops/{$shop_id}/products.json";
            $response = $this->request($endpoint, 'GET');
            
            error_log('Raw API Response: ' . print_r($response, true));

            // Handle different response structures
            if (isset($response['data'])) {
                if (isset($response['data']['data'])) {
                    return $response['data']['data'];
                }
                return $response['data'];
            }

            error_log('Unexpected response structure');
            return [];

        } catch (\Exception $e) {
            error_log('PrintifyAPI getProducts error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    public function createProduct(string $shop_id, array $product_data): array {
        return $this->request(
            "shops/{$shop_id}/products.json",
            'POST',
            $product_data
        );
    }

    private function request(string $endpoint, string $method = 'GET', array $data = []): array {
        $url = $this->getBaseUrl() . '/' . ltrim($endpoint, '/');
        
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getApiKey(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];

        if (!empty($data)) {
            $options['body'] = wp_json_encode($data);
        }

        error_log('Making API request to: ' . $url);
        error_log('With options: ' . print_r($options, true));

        return $this->client->request($url, $method, $options);
    }
}
