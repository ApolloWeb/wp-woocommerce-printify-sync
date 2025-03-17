<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class RateLimiter
{
    private const CACHE_PREFIX = 'wpwps_rate_limit_';
    private const WINDOW_SIZE = 60; // 1 minute

    public function allowRequest(string $key, int $maxRequests): bool
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $currentTime = time();
        $windowStart = $currentTime - self::WINDOW_SIZE;

        // Get current requests
        $requests = get_transient($cacheKey) ?: [];

        // Remove old requests
        $requests = array_filter($requests, fn($timestamp) => $timestamp >= $windowStart);

        // Check if we're over the limit
        if (count($requests) >= $maxRequests) {
            return false;
        }

        // Add new request
        $requests[] = $currentTime;
        set_transient($cacheKey, $requests, self::WINDOW_SIZE);

        return true;
    }
}