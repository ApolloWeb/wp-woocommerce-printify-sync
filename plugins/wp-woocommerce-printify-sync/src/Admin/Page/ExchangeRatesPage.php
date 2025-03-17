<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Page;

use ApolloWeb\WPWooCommercePrintifySync\Service\{
    CurrencyService,
    GeoService
};

class ExchangeRatesPage extends AbstractAdminPage
{
    private CurrencyService $currencyService;
    private GeoService $geoService;

    public function __construct(
        CurrencyService $currencyService,
        GeoService $geoService
    ) {
        $this->currencyService = $currencyService;
        $this->geoService = $geoService;
    }

    public function getTitle(): string
    {
        return __('Exchange Rates', 'wp-woocommerce-printify-sync');
    }

    public function getMenuTitle(): string
    {
        return __('Exchange Rates', 'wp-woocommerce-printify-sync');
    }

    public function getCapability(): string
    {
        return 'manage_options';
    }

    public function getMenuSlug(): string
    {
        return 'wpwps-exchange-rates';
    }

    public function register(): void
    {
        parent::register();
        add_action('wp_ajax_wpwps_update_rates', [$this, 'handleUpdateRates']);
        add_action('wp_ajax_wpwps_get_rate_history', [$this, 'handleGetRateHistory']);
    }

    public function render(): void
    {
        if (!current_user_can($this->getCapability())) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $baseCurrency = get_woocommerce_currency();
        $rates = $this->currencyService->getRates($baseCurrency);
        $history = $this->currencyService->getRateHistory($baseCurrency);
        $popularCurrencies = $this->getPopularCurrencies();

        $this->renderTemplate('exchange-rates', [
            'rates' => $rates,
            'history' => $history,
            'baseCurrency' => $baseCurrency,
            'popularCurrencies' => $popularCurrencies,
            'lastUpdate' => $this->currencyService->getLastUpdate(),
            'stats' => $this->currencyService->getStats()
        ]);
    }

    private function getPopularCurrencies(): array
    {
        return [
            'USD' => [
                'name' => 'US Dollar',
                'symbol' => '$',
                'flag' => 'us'
            ],
            'EUR' => [
                'name' => 'Euro',
                'symbol' => '€',
                'flag' => 'eu'
            ],
            'GBP' => [
                'name' => 'British Pound',
                'symbol' => '£',
                'flag' => 'gb'
            ],
            'CAD' => [
                'name' => 'Canadian Dollar',
                'symbol' => 'C$',
                'flag' => 'ca'
            ],
            'AUD' => [
                'name' => 'Australian Dollar',
                'symbol' => 'A$',
                'flag' => 'au'
            ]
        ];
    }

    public function handleUpdateRates(): void
    {
        check_ajax_referer('wpwps_exchange_rates', 'nonce');

        if (!current_user_can($this->getCapability())) {
            wp_send_json_error(['message' => __('Permission denied.', 'wp-woocommerce-printify-sync')]);
        }

        try {
            $result = $this->currencyService->updateRates();
            wp_send_json_success([
                'message' => __('Exchange rates updated successfully!', 'wp-woocommerce-printify-sync'),
                'rates' => $result['rates'],
                'lastUpdate' => $result['last_update']
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handleGetRateHistory(): void
    {
        check_ajax_referer('wpwps_exchange_rates', 'nonce');

        if (!current_user_can($this->getCapability())) {
            wp_send_json_error(['message' => __('Permission denied.', 'wp-woocommerce-printify-sync')]);
        }

        $currency = sanitize_text_field($_POST['currency'] ?? '');
        $period = sanitize_text_field($_POST['period'] ?? '30d');

        try {
            $history = $this->currencyService->getRateHistory(get_woocommerce_currency(), $currency, $period);
            wp_send_json_success(['history' => $history]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}