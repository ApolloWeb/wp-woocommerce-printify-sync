<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Cache
{
    private const PREFIX = 'wpwps_';
    private const PRODUCTS_KEY = 'products_';
    private const DEFAULT_EXPIRATION = 3600; // 1 hour

    public static function getProducts(string $shopId): ?array
    {
        return get_transient(self::PREFIX . self::PRODUCTS_KEY . $shopId);
    }

    public static function setProducts(string $shopId, array $products, int $expiration = self::DEFAULT_EXPIRATION): bool
    {
        return set_transient(
            self::PREFIX . self::PRODUCTS_KEY . $shopId,
            $products,
            $expiration
        );
    }

    public static function deleteProducts(string $shopId): bool
    {
        return delete_transient(self::PREFIX . self::PRODUCTS_KEY . $shopId);
    }
}
