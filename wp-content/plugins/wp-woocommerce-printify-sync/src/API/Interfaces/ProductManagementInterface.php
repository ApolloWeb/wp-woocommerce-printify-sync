<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API\Interfaces;

interface ProductManagementInterface
{
    /**
     * Get all products from a shop
     *
     * @param string $shopId
     * @param int $page
     * @param int $perPage
     * @param bool $publishedOnly
     * @return array
     */
    public function getProducts(string $shopId, int $page = 1, int $perPage = 10, bool $publishedOnly = false): array;

    /**
     * Get a specific product
     *
     * @param string $shopId
     * @param string $productId
     * @return array
     */
    public function getProduct(string $shopId, string $productId): array;

    /**
     * Get cached products or fetch from API
     *
     * @param string $shopId
     * @param bool $useCache
     * @param int $cacheExpiration
     * @return array
     */
    public function getCachedProducts(string $shopId, bool $useCache = true, int $cacheExpiration = 3600): array;
}
