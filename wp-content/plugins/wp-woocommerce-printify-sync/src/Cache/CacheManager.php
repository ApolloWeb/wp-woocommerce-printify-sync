<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Cache;

class CacheManager
{
    private const CACHE_GROUP = 'wpwps_cache';
    private const CACHE_EXPIRATION = 3600; // 1 hour default

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

    public function getApiResponse(string $endpoint, array $params = []): ?array
    {
        $cache_key = $this->generateApiCacheKey($endpoint, $params);
        return $this->get($cache_key);
    }

    public function setApiResponse(string $endpoint, array $params, array $response, int $expiration = self::CACHE_EXPIRATION): bool
    {
        $cache_key = $this->generateApiCacheKey($endpoint, $params);
        return $this->set($cache_key, $response, $expiration);
    }

    private function generateApiCacheKey(string $endpoint, array $params): string
    {
        return md5($endpoint . serialize($params));
    }
}