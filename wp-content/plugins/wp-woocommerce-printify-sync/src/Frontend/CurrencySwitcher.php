<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Frontend;

use ApolloWeb\WPWooCommercePrintifySync\Services\{
    ConfigService,
    CurrencyService,
    GeolocationService
};

class CurrencySwitcher
{
    private ConfigService $config;
    private CurrencyService $currencyService;
    private GeolocationService $geoService;

    public function __construct(
        ConfigService $config,
        CurrencyService $currencyService,
        GeolocationService $geoService
    ) {
        $this->config = $config;
        $this->currencyService = $currencyService;
        $this->geoService = $geoService;
        
        $this->initHooks();
    }

    private function initHooks(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('woocommerce_before_shop_loop', [$this, 'renderSwitcher']);
        add_action('woocommerce_before_single_product', [$this, 'renderSwitcher']);
        add_filter('woocommerce_currency', [$this, 'getCurrentCurrency']);
        add_filter('woocommerce_price_html', [$this, 'modifyPriceHtml'], 10, 2);
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_style(
            'wpwps-currency-switcher',
            WPWPS_PLUGIN_URL . 'assets/css/currency-switcher.css',
            [],
            '2025.03.15'
        );

        wp_enqueue_script(
            'wpwps-currency-switcher',
            WPWPS_PLUGIN_URL . 'assets/js/currency-switcher.js',
            ['jquery'],
            '2025.03.15',
            true
        );

        wp_localize_script('wpwps-currency-switcher', 'wpwpsCurrency', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-currency'),
            'currentCurrency' => $this->getCurrentCurrency(),
            'defaultCurrency' => $this->config->get('default_currency', 'USD')
        ]);
    }

    public function renderSwitcher(): void
    {
        $currentCurrency = $this->getCurrentCurrency();
        $availableCurrencies = $this->getAvailableCurrencies();
        ?>
        <div class="wpwps-currency-switcher">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <?php echo esc_html($currentCurrency); ?>
                </button>
                <ul class="dropdown-menu">
                    <?php foreach ($availableCurrencies as $code => $name): ?>
                        <li>
                            <a class="dropdown-item <?php echo $code === $currentCurrency ? 'active' : ''; ?>"
                               href="#"
                               data-currency="<?php echo esc_attr($code); ?>">
                                <?php echo esc_html($name . ' (' . $code . ')'); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    public function getCurrentCurrency(): string
    {
        $currency = WC()->session->get('wpwps_currency');
        
        if (!$currency && $this->config->get('enable_geolocation', true)) {
            try {
                $location = $this->geoService->getLocation();
                $currency = $location['currency'];
                WC()->session->set('wpwps_currency', $currency);
            } catch (\Exception $e) {
                $currency = $this->config->get('default_currency', 'USD');
            }
        }

        return $currency ?: $this->config->get('default_currency', 'USD');
    }

    public function modifyPriceHtml(string $priceHtml, \WC_Product $product): string
    {
        $currentCurrency = $this->getCurrentCurrency();
        $defaultCurrency = $this->config->get('default_currency', 'USD');

        if ($currentCurrency === $defaultCurrency) {
            return $priceHtml;
        }

        try {
            $regularPrice = $product->get_regular_price();
            $salePrice = $product->get_sale_price();

            if ($regularPrice) {
                $convertedRegular = $this->currencyService->convertPrice(
                    (float)$regularPrice,
                    $defaultCurrency,
                    $currentCurrency
                );

                if ($salePrice) {
                    $convertedSale = $this->currencyService->convertPrice(
                        (float)$salePrice,
                        $defaultCurrency,
                        $currentCurrency
                    );

                    $priceHtml = wc_format_sale_price(
                        wc_price($convertedRegular, ['currency' => $currentCurrency]),
                        wc_price($convertedSale, ['currency' => $currentCurrency])
                    );
                } else {
                    $priceHtml = wc_price($convertedRegular, ['currency' => $currentCurrency]);
                }
            }
        } catch (\Exception $e) {
            error_log('Currency conversion failed: ' . $e->getMessage());
        }

        return $priceHtml;
    }

    private function getAvailableCurrencies(): array
    {
        $currencies = get_woocommerce_currencies();
        $enabled = $this->config->get('enabled_currencies', ['USD', 'EUR', 'GBP']);
        
        return array_intersect_key($currencies, array_flip($enabled));
    }
}