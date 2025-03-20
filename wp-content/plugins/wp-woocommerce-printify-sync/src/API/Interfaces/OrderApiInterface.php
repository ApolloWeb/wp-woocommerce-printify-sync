<?php
namespace ApolloWeb\WPWooCommercePrintifySync\API\Interfaces;

interface OrderApiInterface 
{
    public function getOrders(string $shopId, int $page = 1, int $perPage = 10): array;
    public function getOrder(string $shopId, string $orderId): array;
    public function getCachedOrders(string $shopId, bool $useCache = true): array;
}
