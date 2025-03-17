<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Cache;

use ApolloWeb\WPWooCommercePrintifySync\Services\ConfigService;

class CurrencyCache
{
    private const CACHE_GROUP = 'wpwps_currency';
    private const CACHE_KEY_PREFIX = 'rate_';
    private ConfigService $config;

    public function __construct(ConfigService $config)
    {
        $this->config = $config;
    }

    public function getRate(string $fromCurrency, string $toCurrency): ?float
    {
        $cacheKey = $this->getCacheKey($fromCurrency, $toCurrency);
        
        if (wp_using_ext_object_cache()) {
            $rate = wp_cache_get($cacheKey, self::CACHE_GROUP);
        } else {
            $rates = get_transient(self::CACHE_GROUP);
            $rate = $rates[$cacheKey] ?? null;
        }

        return $rate !== false ? (float)$rate : null;
    }

    public function setRate(string $fromCurrency, string $toCurrency, float $rate): void
    {
        $cacheKey = $this->getCacheKey($fromCurrency, $toCurrency);
        $expiration = (int)$this->config->get('cache_duration', 3600);

        if (wp_using_ext_object_cache()) {
            wp_cache_set($cacheKey, $rate, self::CACHE_GROUP, $expiration);
        } else {
            $rates = get_transient(self::CACHE_GROUP) ?: [];
            $rates[$cacheKey] = $rate;
            set_transient(self::CACHE_GROUP, $rates, $expiration);
        }
    }

    public function clearRates(): void
    {
        if (wp_using_ext_object_cache()) {
            wp_cache_delete_group(self::CACHE_GROUP);
        } else {
            delete_transient(self::CACHE_GROUP);
        }
    }

    private function getCacheKey(string $fromCurrency, string $toCurrency): string
    {
        return self::CACHE_KEY_PREFIX . strtolower($fromCurrency) . '_' . strtolower($toCurrency);
    }
}