<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API;

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

    public function getOrders(string $shopId, int $page = 1, int $perPage = 10, array $filters = []): array
    {
        // Ensure we don't exceed API maximum limit - Printify orders API maximum is 10
        $perPage = min($perPage, 10); // Maximum allowed by Printify API is 10 (not 50)
        
        // Use the correct parameter names for Printify orders API
        // According to Printify API docs, the orders endpoint uses 'limit' instead of 'per_page'
        $queryParams = [
            'limit' => $perPage,
            'page' => $page
        ];
        
        // Add any additional filters if specified
        if (!empty($filters)) {
            // Only add supported filter parameters (status, sku)
            if (isset($filters['status'])) {
                $queryParams['status'] = $filters['status'];
            }
            if (isset($filters['sku'])) {
                $queryParams['sku'] = $filters['sku'];
            }
        }
        
        try {
            error_log("DEBUG: Making Printify API GET request to shops/{$shopId}/orders.json with params: " . json_encode($queryParams));
            
            // Make the request
            $data = $this->client->request("shops/{$shopId}/orders.json", 'GET', $queryParams);
            
            // Log the raw response for debugging
            error_log("DEBUG: Printify API orders response: " . json_encode(array_keys($data)));
            
            // According to documentation, the response should include 'current_page' and 'data' keys
            if (isset($data['data'])) {
                return [
                    'data' => $data['data'],
                    'total' => isset($data['current_page']) ? (int)($data['current_page']['total'] ?? count($data['data'])) : count($data['data']),
                    'current_page' => isset($data['current_page']) ? (int)($data['current_page']['number'] ?? $page) : $page,
                    'per_page' => isset($data['current_page']) ? (int)($data['current_page']['limit'] ?? $perPage) : $perPage,
                    'last_page' => isset($data['current_page']) ? (int)ceil(($data['current_page']['total'] ?? count($data['data'])) / $perPage) : 1
                ];
            } else {
                // Attempt to normalize the response format if different from expected
                error_log("DEBUG: Orders API response format is different than expected. Keys: " . json_encode(array_keys($data)));
                
                if (is_array($data) && !empty($data)) {
                    // The response might be an array of orders directly
                    return [
                        'data' => $data,
                        'total' => count($data),
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'last_page' => 1
                    ];
                }
                
                throw new \Exception('Invalid response format from Printify API');
            }
        } catch (\Exception $e) {
            error_log('Printify API getOrders error: ' . $e->getMessage());
            throw $e;
        }
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

        try {
            $allOrders = [];
            $page = 1;
            $perPage = 10; // Changed from 50 to 10 to match Printify API limit for orders
            $total = 0;

            do {
                $result = $this->getOrders($shopId, $page, $perPage);
                $allOrders = array_merge($allOrders, $result['data']);
                $total = $result['total'];
                $page++;
            } while (count($allOrders) < $total && $page <= $result['last_page'] && count($allOrders) < 50);

            // Still cap at 50 orders for display purposes
            $allOrders = array_slice($allOrders, 0, 50);
            Cache::setOrders($shopId, $allOrders, $cacheExpiration);

            return $allOrders;
        } catch (\Exception $e) {
            error_log('Error fetching cached orders: ' . $e->getMessage());
            return [];
        }
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
