<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class CurrencyConverter extends AbstractService
{
    private const EXCHANGE_ENDPOINT = 'https://api.exchangerate-api.com/v4/latest/USD';
    private const CACHE_GROUP = 'printify_currency';
    private const CACHE_DURATION = 3600; // 1 hour

    private array $rates = [];

    public function convert(float $amount, string $from = 'USD', string $to = ''): float
    {
        try {
            if (empty($to)) {
                $to = get_woocommerce_currency();
            }

            // If same currency, return original amount
            if ($from === $to) {
                return $amount;
            }

            $rates = $this->getRates();
            if (!isset($rates[$from]) || !isset($rates[$to])) {
                throw new \Exception('Currency rate not available');
            }

            // Convert through USD as base
            $inUSD = $amount / $rates[$from];
            $converted = $inUSD * $rates[$to];

            return round($converted, 2);

        } catch (\Exception $e) {
            $this->logError('convert', $e, [
                'amount' => $amount,
                'from' => $from,
                'to' => $to
            ]);
            return $amount;
        }
    }

    private function getRates(): array
    {
        if (!empty($this->rates)) {
            return $this->rates;
        }

        $cacheKey = 'exchange_rates';
        $cached = wp_cache_get($cacheKey, self::CACHE_GROUP);
        
        if ($cached) {
            $this->rates = $cached;
            return $this->rates;
        }

        $rates = $this->fetchRates();
        wp_cache_set($cacheKey, $rates, self::CACHE_GROUP, self::CACHE_DURATION);
        
        $this->rates = $rates;
        return $this->rates;
    }

    private function fetchRates(): array
    {
        $response = wp_remote_get(self::EXCHANGE_ENDPOINT);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['rates'])) {
            throw new \Exception('Invalid exchange rate response');
        }

        return $data['rates'];
    }

    public function formatPrice(float $amount, string $currency = ''): string
    {
        if (empty($currency)) {
            $currency = get_woocommerce_currency();
        }

        return wc_price($amount, [
            'currency' => $currency
        ]);
    }
}