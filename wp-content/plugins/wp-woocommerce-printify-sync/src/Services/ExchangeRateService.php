<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Foundation\AppContext;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LoggerAwareTrait;

class ExchangeRateService
{
    use LoggerAwareTrait;

    private AppContext $context;
    private string $apiKey;
    private const CACHE_KEY = 'wpwps_exchange_rates';
    private const CACHE_EXPIRY = 3600; // 1 hour
    private string $currentTime = '2025-03-15 20:18:01';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        $this->context = AppContext::getInstance();
        $this->apiKey = get_option('wpwps_exchange_rate_api_key');
    }

    public function updateRates(): void
    {
        try {
            $rates = $this->fetchLatestRates();
            $this->saveRates($rates);

            $this->log('info', 'Exchange rates updated successfully', [
                'currencies_count' => count($rates)
            ]);

        } catch (\Exception $e) {
            $this->log('error', 'Failed to update exchange rates', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->getRate($from, $to);
        return $amount * $rate;
    }

    public function getRate(string $from, string $to): float
    {
        global $wpdb;

        $rate = $wpdb->get_var($wpdb->prepare(
            "SELECT rate 
            FROM {$wpdb->prefix}wpwps_exchange_rates 
            WHERE currency_from = %s 
            AND currency_to = %s",
            $from,
            $to
        ));

        if (!$rate) {
            throw new \Exception("Exchange rate not found for {$from} to {$to}");
        }

        return (float) $rate;
    }

    private function fetchLatestRates(): array
    {
        $response = wp_remote_get(
            'https://api.exchangerate-api.com/v4/latest/USD',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
                ]
            ]
        );

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['rates'])) {
            throw new \Exception('Invalid response from exchange rate API');
        }

        return $data['rates'];
    }

    private function saveRates(array $rates): void
    {
        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Clear existing rates
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}wpwps_exchange_rates");

            // Insert new rates
            foreach ($rates as $currency => $rate) {
                $wpdb->insert(
                    $wpdb->prefix . 'wpwps_exchange_rates',
                    [
                        'currency_from' => 'USD',
                        'currency_to' => $currency,
                        'rate' => $rate,
                        'last_updated' => $this->currentTime
                    ]
                );
            }

            $wpdb->query('COMMIT');

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }
}