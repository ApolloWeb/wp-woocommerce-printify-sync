<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core\Assets;

class Enqueue
{
    private $assets = [];
    private $baseUrl;

    public function __construct()
    {
        // Update to use plugin_dir_url() with __FILE__
        $this->baseUrl = plugin_dir_url(dirname(dirname(dirname(__FILE__))));
        $this->setupAssets();
    }

    private function setupAssets()
    {
        // Set up assets array for each page
        $this->assets = [
            'wpwps-dashboard' => [
                'styles' => ['wpwps-bootstrap', 'wpwps-fontawesome', 'wpwps-common', 'wpwps-dashboard'],
                'scripts' => ['wpwps-bootstrap', 'wpwps-chartjs', 'wpwps-common', 'wpwps-dashboard']
            ],
            'wpwps-products' => [
                'styles' => ['wpwps-bootstrap', 'wpwps-fontawesome', 'wpwps-common', 'wpwps-products'],
                'scripts' => ['wpwps-bootstrap', 'wpwps-common', 'wpwps-products']
            ],
            'wpwps-orders' => [
                'styles' => ['wpwps-bootstrap', 'wpwps-fontawesome', 'wpwps-common', 'wpwps-orders'],
                'scripts' => ['wpwps-bootstrap', 'wpwps-common', 'wpwps-orders']
            ],
            'wpwps-settings' => [
                'styles' => ['wpwps-bootstrap', 'wpwps-fontawesome', 'wpwps-common', 'wpwps-settings'],
                'scripts' => ['wpwps-bootstrap', 'wpwps-common', 'wpwps-settings']
            ]
        ];
    }

    public function registerAssets()
    {
        // Only register on admin pages
        if (!is_admin()) {
            return;
        }
        
        // Third party assets
        wp_register_style('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
        wp_register_style('wpwps-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
        
        wp_register_script('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], null, true);
        wp_register_script('wpwps-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);

        // Common assets - these must load first
        wp_register_style('wpwps-common', $this->baseUrl . 'assets/css/wpwps-common.css', [], WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION);
        wp_register_script('wpwps-common', $this->baseUrl . 'assets/js/wpwps-common.js', ['jquery'], WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION, true);

        // Debug assets
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wp_register_script('wpwps-ajax-debug', $this->baseUrl . 'assets/js/wpwps-ajax-debug.js', ['jquery'], WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION, true);
        }

        // Debug scripts
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wp_register_script('wpwps-debug', $this->baseUrl . 'assets/js/wpwps-debug.js', ['jquery'], WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION, true);
        }

        // Register a dedicated script for the clear data button
        wp_register_script('wpwps-clear-data', $this->baseUrl . 'assets/js/wpwps-clear-data.js', ['jquery'], WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION, true);

        // Register page specific assets
        $pages = ['dashboard', 'products', 'orders', 'settings'];
        foreach ($pages as $page) {
            wp_register_style("wpwps-{$page}", $this->baseUrl . "assets/css/wpwps-{$page}.css", ['wpwps-common'], WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION);
            // Make sure all page-specific scripts depend on wpwps-common
            wp_register_script("wpwps-{$page}", $this->baseUrl . "assets/js/wpwps-{$page}.js", ['jquery', 'wpwps-common'], WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION, true);
        }
    }

    public function enqueuePageAssets($pageSlug)
    {
        // Make sure we register our assets first
        $this->registerAssets();
        
        // Always enqueue common assets first
        wp_enqueue_style('wpwps-common');
        wp_enqueue_script('wpwps-common');
        
        // Get currency symbols from WooCommerce if available
        $currency_symbols = $this->getCurrencySymbols();
        $selected_currency = get_option('wpwps_currency', 'GBP');
        
        // Localize script with our data
        wp_localize_script('wpwps-common', 'wpwps_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_nonce'),
            'currency' => $selected_currency,
            'currency_symbols' => $currency_symbols
        ]);
        
        // Add debug script in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wp_enqueue_script('wpwps-debug');
        }

        // Page specific assets
        if (isset($this->assets[$pageSlug])) {
            foreach ($this->assets[$pageSlug]['styles'] as $style) {
                wp_enqueue_style($style);
            }
            foreach ($this->assets[$pageSlug]['scripts'] as $script) {
                wp_enqueue_script($script);
            }
        }
    }
    
    /**
     * Get currency symbols from WooCommerce
     *
     * @return array
     */
    private function getCurrencySymbols(): array
    {
        $symbols = [
            'GBP' => '£',
            'USD' => '$',
            'EUR' => '€'
        ];
        
        // If WooCommerce is active, use its currency symbols
        if (function_exists('get_woocommerce_currency_symbols')) {
            $wc_symbols = get_woocommerce_currency_symbols();
            
            // Only update our default set with what we find in WooCommerce
            foreach ($symbols as $code => $symbol) {
                if (isset($wc_symbols[$code])) {
                    $symbols[$code] = $wc_symbols[$code];
                }
            }
            
            // Add any additional currencies that might be useful
            $additional_currencies = ['AUD', 'CAD', 'JPY', 'CHF', 'NZD'];
            foreach ($additional_currencies as $currency) {
                if (isset($wc_symbols[$currency])) {
                    $symbols[$currency] = $wc_symbols[$currency];
                }
            }
        }
        
        return $symbols;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
}
