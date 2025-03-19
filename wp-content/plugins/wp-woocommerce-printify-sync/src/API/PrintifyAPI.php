<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\API\Interfaces\PrintifyAPIInterface;
use ApolloWeb\WPWooCommercePrintifySync\Core\Cache;

class PrintifyAPI implements PrintifyAPIInterface
{
    private $apiKey;
    private $endpoint;
    
    public function __construct(string $apiKey, string $endpoint)
    {
        $this->apiKey = $apiKey;
        $this->endpoint = rtrim($endpoint, '/');
    }

    private function makeRequest(string $path, string $method = 'GET', array $params = []): array
    {
        $url = $this->endpoint . '/' . ltrim($path, '/');
        
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 15
        ];
        
        if ($method !== 'GET' && !empty($params)) {
            $args['body'] = json_encode($params);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception('Connection error: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code < 200 || $status_code >= 300) {
            $error_msg = isset($data['error']) ? $data['error'] : 'Unknown error';
            throw new \Exception("API responded with code {$status_code}: {$error_msg}");
        }
        
        return $data;
    }

    public function getShops(): array
    {
        return $this->makeRequest('shops.json');
    }

    public function getProducts(string $shopId, int $page = 1, int $perPage = 10, bool $publishedOnly = false): array
    {
        $queryParams = [
            'limit' => $perPage,
            'page' => $page
        ];
        
        if (!$publishedOnly) {
            $queryParams['status'] = 'all';
        }
        
        $data = $this->makeRequest(
            "shops/{$shopId}/products.json",
            'GET',
            $queryParams
        );

        return [
            'data' => $data['data'] ?? [],
            'total' => (int)($data['current_page']['total'] ?? 0),
            'current_page' => (int)($data['current_page']['number'] ?? 1),
            'per_page' => (int)($data['current_page']['limit'] ?? $perPage),
            'last_page' => (int)ceil(($data['current_page']['total'] ?? 0) / $perPage)
        ];
    }

    public function getProduct(string $shopId, string $productId): array
    {
        return $this->makeRequest("shops/{$shopId}/products/{$productId}.json");
    }

    public function getCachedProducts(string $shopId, bool $useCache = true, int $cacheExpiration = 3600): array
    {
        if ($useCache) {
            $cachedProducts = Cache::getProducts($shopId);
            if ($cachedProducts !== null) {
                return $cachedProducts;
            }
        }

        // Fetch first 50 products from API
        $allProducts = [];
        $page = 1;
        $perRequest = 50;

        do {
            $result = $this->getProducts($shopId, $page, $perRequest);
            $allProducts = array_merge($allProducts, $result['data']);
            $page++;
        } while (count($allProducts) < 50 && $page <= $result['last_page']);

        // Limit to 50 products
        $allProducts = array_slice($allProducts, 0, 50);

        // Cache the results
        Cache::setProducts($shopId, $allProducts, $cacheExpiration);

        return $allProducts;
    }

    public function testConnection(): bool
    {
        try {
            $this->getShops();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
