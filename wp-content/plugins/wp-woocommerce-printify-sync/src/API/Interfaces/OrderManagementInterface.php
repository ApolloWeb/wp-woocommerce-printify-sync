<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API\Interfaces;

interface OrderManagementInterface
{
    /**
     * Get orders from a Printify shop
     *
     * @param string $shopId
     * @param int $page
     * @param int $perPage
     * @param array $filters Additional filters like status, date range
     * @return array
     */
    public function getOrders(string $shopId, int $page = 1, int $perPage = 10, array $filters = []): array;

    /**
     * Get a specific order from Printify
     *
     * @param string $shopId
     * @param string $orderId
     * @return array
     */
    public function getOrder(string $shopId, string $orderId): array;

    /**
     * Get cached orders or fetch from API
     *
     * @param string $shopId
     * @param bool $useCache
     * @param int $cacheExpiration
     * @return array
     */
    public function getCachedOrders(string $shopId, bool $useCache = true, int $cacheExpiration = 3600): array;
}
