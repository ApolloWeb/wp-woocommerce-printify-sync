<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface PrintifyApiInterface {
    /**
     * Get list of available shops
     */
    public function getShops(): array;
    
    /**
     * Get catalog of products
     */
    public function getCatalog(array $params = []): array;
    
    /**
     * Get list of print providers
     */
    public function getPrintProviders(): array;
    
    /**
     * Get shipping info for provider
     */
    public function getProviderShipping(int $providerId): array;
    
    /**
     * Create a new order
     */
    public function createOrder(array $orderData): array;
    
    /**
     * Cancel an order
     */
    public function cancelOrder(string $orderId): bool;
    
    /**
     * Calculate shipping costs
     */
    public function calculateShipping(array $items, array $address): array;
    
    /**
     * Submit product for publishing
     */
    public function publishProduct(array $productData): array;
}
