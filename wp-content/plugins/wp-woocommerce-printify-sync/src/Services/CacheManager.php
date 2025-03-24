<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class CacheManager {
    private const CACHE_GROUP = 'wpwps_cache';
    private const DEFAULT_TTL = 3600; // 1 hour

    public function get(string $key) {
        return wp_cache_get($key, self::CACHE_GROUP);
    }

    public function set(string $key, $value, int $ttl = self::DEFAULT_TTL): void {
        wp_cache_set($key, $value, self::CACHE_GROUP, $ttl);
    }

    public function delete(string $key): void {
        wp_cache_delete($key, self::CACHE_GROUP);
    }

    public function generateKey(string $prefix, array $args): string {
        return $prefix . '_' . md5(serialize($args));
    }
}
