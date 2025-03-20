<?php
namespace ApolloWeb\WPWooCommercePrintifySync\API\Interfaces;

interface ProductApiInterface 
{
    public function getProducts(string $shopId, int $page = 1, int $perPage = 10): array;
    public function getProduct(string $shopId, string $productId): array;
    public function getCachedProducts(string $shopId, bool $useCache = true): array;
}
