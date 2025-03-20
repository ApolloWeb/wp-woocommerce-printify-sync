<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API\Interfaces;

interface PrintifyAPIInterface
{
    /**
     * Get all products from a shop
     */
    public function getProducts(string $shopId, int $page = 1, int $perPage = 10, bool $publishedOnly = false): array;

    /**
     * Get a specific product
     */
    public function getProduct(string $shopId, string $productId): array;
    
    /**
     * Get cached products
     */
    public function getCachedProducts(string $shopId, bool $useCache = true, int $cacheExpiration = 3600): array;

    /**
     * Get shops from Printify
     */
    public function getShops(): array;

    /**
     * Get orders from a shop
     */
    public function getOrders(string $shopId, int $page = 1, int $perPage = 10): array;

    /**
     * Get a specific order
     */
    public function getOrder(string $shopId, string $orderId): array;

    /**
     * Get cached orders
     */
    public function getCachedOrders(string $shopId, bool $useCache = true, int $cacheExpiration = 3600): array;

    /**
     * Test API connection
     */
    public function testConnection(): bool;
}
