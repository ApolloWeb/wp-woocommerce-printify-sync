<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class RateLimiter {
    private string $cache_key_prefix = 'wpwps_rate_limit_';
    private array $limits = [];

    public function __construct() 
    {
        $this->limits = [
            'api' => get_option('wpwps_api_rate_limit', 60),
            'sync' => get_option('wpwps_sync_rate_limit', 300)
        ];
    }

    public function isAllowed(string $key): bool 
    {
        $cache_key = $this->cache_key_prefix . $key;
        $current = get_transient($cache_key);
        
        if ($current === false) {
            $this->reset($key);
            return true;
        }

        if ($current <= 0) {
            return false;
        }

        $this->decrement($key);
        return true;
    }

    public function reset(string $key): void 
    {
        $limit = $this->limits[$key] ?? 60;
        set_transient(
            $this->cache_key_prefix . $key,
            $limit,
            HOUR_IN_SECONDS
        );
    }

    private function decrement(string $key): void 
    {
        $cache_key = $this->cache_key_prefix . $key;
        $current = get_transient($cache_key);
        
        if ($current > 0) {
            set_transient($cache_key, --$current, HOUR_IN_SECONDS);
        }
    }
}
