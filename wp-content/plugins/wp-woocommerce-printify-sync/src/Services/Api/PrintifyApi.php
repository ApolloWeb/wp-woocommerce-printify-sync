<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Api;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\PrintifyApiInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\RateLimiterInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\ConfigInterface;

class PrintifyApi implements PrintifyApiInterface {
    private $client;
    private $rateLimiter;
    private $config;
    
    public function __construct(
        ConfigInterface $config,
        RateLimiterInterface $rateLimiter
    ) {
        $this->config = $config;
        $this->rateLimiter = $rateLimiter;
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.printify.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->get('api_key'),
                'Accept' => 'application/json',
            ]
        ]);
    }

    public function getShops(): array {
        return $this->request('GET', 'shops.json');
    }

    public function getCatalog(array $params = []): array {
        return $this->request('GET', 'catalog/blueprints.json', ['query' => $params]);
    }

    public function getPrintProviders(): array {
        return $this->request('GET', 'print-providers.json');
    }

    public function getProviderShipping(int $providerId): array {
        return $this->request('GET', "print-providers/{$providerId}/shipping.json");
    }

    public function createOrder(array $orderData): array {
        return $this->request('POST', 'orders.json', ['json' => $orderData]);
    }

    public function calculateShipping(array $items, array $address): array {
        return $this->request('POST', 'shipping/calculate.json', [
            'json' => [
                'line_items' => $items,
                'address' => $address
            ]
        ]);
    }

    public function cancelOrder(string $orderId): bool {
        $response = $this->request('POST', "orders/{$orderId}/cancel.json");
        return $response['success'] ?? false;
    }

    public function publishProduct(array $productData): array {
        return $this->request('POST', 'products.json', ['json' => $productData]);
    }

    private function request(string $method, string $uri, array $options = []): array {
        $this->rateLimiter->checkLimit();
        
        try {
            $response = $this->client->request($method, $uri, array_merge([
                'timeout' => 30,
                'connect_timeout' => 5,
                'http_errors' => false,
            ], $options));

            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($response->getStatusCode() >= 400) {
                throw new \Exception(
                    $data['message'] ?? 'Unknown API error',
                    $response->getStatusCode()
                );
            }

            return $data;
            
        } catch (\Exception $e) {
            do_action('wpwps_api_error', $e, compact('method', 'uri', 'options'));
            throw $e;
        }
    }
}
