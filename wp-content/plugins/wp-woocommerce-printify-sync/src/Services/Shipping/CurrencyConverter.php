<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Shipping;

class CurrencyConverter {
    private array $rates = [];
    private string $base_currency = 'USD';
    private string $cache_key = 'wpwps_exchange_rates';

    public function __construct() 
    {
        $this->loadRates();
    }

    public function convert(float $amount, string $from, string $to): float 
    {
        if ($from === $to) {
            return $amount;
        }

        $from_rate = $this->rates[$from] ?? 1;
        $to_rate = $this->rates[$to] ?? 1;

        return ($amount / $from_rate) * $to_rate;
    }

    public function format(float $amount, string $currency): string 
    {
        return sprintf(
            '%s %s',
            number_format($amount, 2),
            strtoupper($currency)
        );
    }

    private function loadRates(): void 
    {
        $rates = get_transient($this->cache_key);
        
        if ($rates === false) {
            $rates = $this->fetchRates();
            set_transient($this->cache_key, $rates, DAY_IN_SECONDS);
        }

        $this->rates = $rates;
    }

    private function fetchRates(): array 
    {
        // Implement actual exchange rate API call here
        // For now return static rates
        return [
            'USD' => 1.0,
            'EUR' => 0.85,
            'GBP' => 0.73,
            'CAD' => 1.25,
            'AUD' => 1.35
        ];
    }
}
