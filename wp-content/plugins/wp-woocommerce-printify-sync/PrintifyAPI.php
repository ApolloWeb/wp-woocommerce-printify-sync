<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\API\Interfaces\PrintifyAPIInterface;
use ApolloWeb\WPWooCommercePrintifySync\Core\Cache;

class PrintifyAPI implements PrintifyAPIInterface
{
    private $client;
    
    public function __construct(PrintifyHttpClient $client) 
    {
        $this->client = $client;
    }
    
    public function getProducts(string $shopId, int $page = 1, int $perPage = 10, bool $publishedOnly = false): array 
    {
        $queryParams = [
            'limit' => $perPage,
            'page' => $page,
            'status' => $publishedOnly ? 'active' : 'all'
        ];
        
        $data = $this->client->request("shops/{$shopId}/products.json", 'GET', $queryParams);
        return $this->formatPaginatedResponse($data, $page, $perPage);
    }

    public function getProduct(string $shopId, string $productId): array
    {
        return $this->client->request("shops/{$shopId}/products/{$productId}.json");
    }
    
    public function getCachedProducts(string $shopId, bool $useCache = true, int $cacheExpiration = 3600): array
    {
        if ($useCache) {
            $cachedProducts = Cache::getProducts($shopId);
            if ($cachedProducts !== null) {
                return $cachedProducts;
            }
        }

        $allProducts = [];
        $page = 1;
        $perPage = 50;

        do {
            $result = $this->getProducts($shopId, $page, $perPage);
            $allProducts = array_merge($allProducts, $result['data']);
            $page++;
        } while (count($allProducts) < 50 && $page <= $result['last_page']);

        $allProducts = array_slice($allProducts, 0, 50);
        Cache::setProducts($shopId, $allProducts, $cacheExpiration);

        return $allProducts;
    }

    public function getShops(): array 
    {
        return $this->client->request('shops.json');
    }

    public function getOrders(string $shopId, int $page = 1, int $perPage = 10): array
    {
        $queryParams = [
            'limit' => $perPage,
            'page' => $page
        ];
        
        $data = $this->client->request("shops/{$shopId}/orders.json", 'GET', $queryParams);
        return $this->formatPaginatedResponse($data, $page, $perPage);
    }

    public function getOrder(string $shopId, string $orderId): array
    {
        return $this->client->request("shops/{$shopId}/orders/{$orderId}.json");
    }

    public function getCachedOrders(string $shopId, bool $useCache = true, int $cacheExpiration = 3600): array
    {
        if ($useCache) {
            $cachedOrders = Cache::getOrders($shopId);
            if ($cachedOrders !== null) {
                return $cachedOrders;
            }
        }

        $allOrders = [];
        $page = 1;
        $perPage = 50;

        do {
            $result = $this->getOrders($shopId, $page, $perPage);
            $allOrders = array_merge($allOrders, $result['data']);
            $page++;
        } while (count($allOrders) < 50 && $page <= $result['last_page']);

        $allOrders = array_slice($allOrders, 0, 50);
        Cache::setOrders($shopId, $allOrders, $cacheExpiration);

        return $allOrders;
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

    private function formatPaginatedResponse(array $data, int $page, int $perPage): array
    {
        return [
            'data' => $data['data'] ?? [],
            'total' => (int)($data['current_page']['total'] ?? 0),
            'current_page' => (int)($data['current_page']['number'] ?? $page),
            'per_page' => (int)($data['current_page']['limit'] ?? $perPage),
            'last_page' => (int)ceil(($data['current_page']['total'] ?? 0) / $perPage)
        ];
    }
}
