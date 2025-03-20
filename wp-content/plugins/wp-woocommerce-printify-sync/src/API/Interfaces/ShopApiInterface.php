<?php
namespace ApolloWeb\WPWooCommercePrintifySync\API\Interfaces;

interface ShopApiInterface 
{
    public function getShops(): array;
    public function getShop(string $shopId): array;
}
