<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class RateLimiter {
    private const TRANSIENT_PREFIX = 'wpwps_rate_limit_';
    private const DEFAULT_WINDOW = 3600; // 1 hour
    private const DEFAULT_MAX_REQUESTS = 1000;

    public function checkLimit(string $key): bool {
        $transient = self::TRANSIENT_PREFIX . $key;
        $count = get_transient($transient);
        
        if (false === $count) {
            set_transient($transient, 1, self::DEFAULT_WINDOW);
            return true;
        }

        if ($count >= self::DEFAULT_MAX_REQUESTS) {
            return false;
        }

        set_transient($transient, $count + 1, self::DEFAULT_WINDOW);
        return true;
    }

    public function getBackoffTime(string $key, int $attempts): int {
        return min(pow(2, $attempts) * 10, 300); // Max 5 minutes
    }
}
