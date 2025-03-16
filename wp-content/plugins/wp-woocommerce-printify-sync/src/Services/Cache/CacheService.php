<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Cache;

class CacheService
{
    private const CACHE_GROUP = 'printify_sync';
    private const CACHE_EXPIRATION = 3600; // 1 hour

    public function get(string $key)
    {
        return wp_cache_get($key, self::CACHE_GROUP);
    }

    public function set(string $key, $value, int $expiration = self::CACHE_EXPIRATION): bool
    {
        return wp_cache_set($key, $value, self::CACHE_GROUP, $expiration);
    }

    public function delete(string $key): bool
    {
        return wp_cache_delete($key, self::CACHE_GROUP);
    }

    public function flush(): bool
    {
        return wp_cache_flush();
    }
}