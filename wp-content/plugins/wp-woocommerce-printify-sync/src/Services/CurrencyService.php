<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Cache\CurrencyCache;
use ApolloWeb\WPWooCommercePrintifySync\Exceptions\CurrencyException;

class CurrencyService
{
    private ConfigService $config;
    private CurrencyCache $cache;
    private ?array $batchRates = null;

    public function __construct(ConfigService $config, CurrencyCache $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
    }

    public function convertPrice(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        return round($amount * $rate, 2);
    }

    public function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        // Check cache first
        $rate = $this->cache->getRate($fromCurrency, $toCurrency);
        
        if ($rate === null) {
            $rate = $this->fetchExchangeRate($fromCurrency, $toCurrency);
            $this->cache->setRate($fromCurrency, $toCurrency, $rate);
        }

        return $rate;
    }

    public function batchConvert(array $prices, string $fromCurrency, string $toCurrency): array
    {
        if ($fromCurrency === $toCurrency) {
            return $prices;
        }

        // Fetch rate once for all conversions
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        
        return array_map(function($price) use ($rate) {
            return round($price * $rate, 2);
        }, $prices);
    }

    public function prefetchRates(array $currencies): void
    {
        $this->batchRates = [];
        $baseCurrency = $this->config->get('default_currency', 'USD');

        foreach ($currencies as $currency) {
            if ($currency === $baseCurrency) {
                continue;
            }

            $rate = $this->cache->getRate($baseCurrency, $currency);
            if ($rate === null) {
                try {
                    $rate = $this->fetchExchangeRate($baseCurrency, $currency);
                    $this->cache->setRate($baseCurrency, $currency, $rate);
                } catch (\Exception $e) {
                    error_log('Failed to prefetch rate for ' . $currency . ': ' . $e->getMessage());
                    continue;
                }
            }
            $this->batchRates[$currency] = $rate;
        }
    }

    public function clearRatesCache(): void
    {
        $this->cache->clearRates();
        $this->batchRates = null;
    }
}