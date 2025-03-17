<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax;

use ApolloWeb\WPWooCommercePrintifySync\Services\{
    ConfigService,
    CurrencyService
};

class CurrencyHandler
{
    private ConfigService $config;
    private CurrencyService $currencyService;

    public function __construct(ConfigService $config, CurrencyService $currencyService)
    {
        $this->config = $config;
        $this->currencyService = $currencyService;
        
        $this->initHooks();
    }

    private function initHooks(): void
    {
        add_action('wp_ajax_wpwps_switch_currency', [$this, 'handleCurrencySwitch']);
        add_action('wp_ajax_nopriv_wpwps_switch_currency', [$this, 'handleCurrencySwitch']);
        
        add_action('wp_ajax_wpwps_test_currency', [$this, 'handleCurrencyTest']);
    }

    public function handleCurrencySwitch(): void
    {
        try {
            if (!check_ajax_referer('wpwps-currency', 'nonce', false)) {
                throw new \Exception('Invalid nonce');
            }

            $currency = sanitize_text_field($_POST['currency'] ?? '');
            if (!$currency) {
                throw new \Exception('Currency not specified');
            }

            // Validate currency
            $currencies = get_woocommerce_currencies();
            if (!isset($currencies[$currency])) {
                throw new \Exception('Invalid currency');
            }

            // Store in session
            WC()->session->set('wpwps_currency', $currency);

            wp_send_json_success([
                'message' => 'Currency updated successfully',
                'currency' => $currency
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function handleCurrencyTest(): void
    {
        try {
            if (!current_user_can('manage_options')) {
                throw new \Exception('Unauthorized access');
            }

            if (!check_ajax_referer('wpwps-admin', 'nonce', false)) {
                throw new \Exception('Invalid nonce');
            }

            // Test currency conversion
            $result = $this->currencyService->convertPrice(
                100.00,
                'USD',
                'EUR'
            );

            wp_send_json_success([
                'message' => 'Currency conversion test successful',
                'test_result' => [
                    'original' => '100.00 USD',
                    'converted' => number_format($result, 2) . ' EUR',
                    'rate' => $this->currencyService->getExchangeRate('USD', 'EUR')
                ]
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}