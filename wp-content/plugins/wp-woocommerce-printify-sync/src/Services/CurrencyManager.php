<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class CurrencyManager extends AbstractService
{
    private const EXCHANGE_API_ENDPOINT = 'https://api.exchangerate-api.com/v4/latest/';
    private const CRON_HOOK = 'wpwps_update_exchange_rates';

    public function __construct(LoggerInterface $logger, ConfigService $config)
    {
        parent::__construct($logger, $config);
        $this->setupCronJob();
    }

    private function setupCronJob(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'six_hours', self::CRON_HOOK);
        }

        add_action(self::CRON_HOOK, [$this, 'updateExchangeRates']);
    }

    public function updateExchangeRates(): void
    {
        try {
            $baseCurrency = get_woocommerce_currency();
            $response = wp_remote_get(self::EXCHANGE_API_ENDPOINT . $baseCurrency);

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($data['rates'])) {
                throw new \Exception('Invalid exchange rate response');
            }

            update_option('wpwps_exchange_rates', [
                'base' => $baseCurrency,
                'rates' => $data['rates'],
                'updated_at' => $this->getCurrentTime()
            ]);

            $this->logOperation('updateExchangeRates', [
                'base_currency' => $baseCurrency,
                'rates_count' => count($data['rates'])
            ]);

        } catch (\Exception $e) {
            $this->logError('updateExchangeRates', $e);
        }
    }

    public function convertPrice(float $amount, string $from = 'USD'): float
    {
        $to = get_woocommerce_currency();
        
        // If same currency, return original amount
        if ($from === $to) {
            return $amount;
        }

        $rates = get_option('wpwps_exchange_rates');
        if (empty($rates) || empty($rates['rates'][$from])) {
            return $amount;
        }

        // Convert through base currency
        return round($amount * $rates['rates'][$from], 2);
    }

    public function lockOrderPrices(\WC_Order $order): void
    {
        $rates = get_option('wpwps_exchange_rates');
        
        if ($rates) {
            $order->update_meta_data('_wpwps_exchange_rates', $rates);
            $order->save();
        }
    }

    public function getLockedExchangeRate(\WC_Order $order, string $currency = 'USD'): ?float
    {
        $rates = $order->get_meta('_wpwps_exchange_rates');
        
        if (!empty($rates['rates'][$currency])) {
            return (float)$rates['rates'][$currency];
        }

        return null;
    }
}