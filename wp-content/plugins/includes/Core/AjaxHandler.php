<?php
/**
 * Ajax handler class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyService;
use ApolloWeb\WPWooCommercePrintifySync\Services\GeolocationService;
use ApolloWeb\WPWooCommercePrintifySync\Services\CurrencyService;

/**
 * Class AjaxHandler
 */
class AjaxHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Admin AJAX endpoints
        add_action('wp_ajax_wpps_test_printify_api', [$this, 'testPrintifyApi']);
        add_action('wp_ajax_wpps_save_settings', [$this, 'saveSettings']);
        add_action('wp_ajax_wpps_sync_products', [$this, 'syncProducts']);
        add_action('wp_ajax_wpps_test_geolocation_api', [$this, 'testGeolocationApi']);
        add_action('wp_ajax_wpps_test_currency_api', [$this, 'testCurrencyApi']);
        add_action('wp_ajax_wpps_get_logs', [$this, 'getLogs']);
        
        // Frontend AJAX endpoints
        add_action('wp_ajax_nopriv_wpps_get_shipping_rates', [$this, 'getShippingRates']);
        add_action('wp_ajax_wpps_get_shipping_rates', [$this, 'getShippingRates']);
    }

    /**
     * Test Printify API connection
     *
     * @return void
     */
    public function testPrintifyApi(): void
    {
        // Check nonce
        if (!$this->verifyAjaxNonce('wpps-ajax-nonce')) {
            return;
        }

        // Get API key from request
        $apiKey = sanitize_text_field($_POST['api_key'] ?? '');
        
        // Test API connection
        $printifyService = new PrintifyService($apiKey);
        $result = $printifyService->testConnection();
        
        // Return result
        if ($result['success']) {
            wp_send_json_success([
                'message' => __('Successfully connected to Printify API!', 'wp-woocommerce-printify-sync'),
                'data' => $result['data']
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['error'] ?? __('Failed to connect to Printify API.', 'wp-woocommerce-printify-sync')
            ]);
        }
    }
    
    /**
     * Test Geolocation API connection
     *
     * @return void
     */
    public function testGeolocationApi(): void
    {
        // Check nonce
        if (!$this->verifyAjaxNonce('wpps-ajax-nonce')) {
            return;
        }

        // Get API key from request
        $apiKey = sanitize_text_field($_POST['api_key'] ?? '');
        
        // Test API connection
        $geolocationService = new GeolocationService($apiKey);
        $result = $geolocationService->testConnection();
        
        // Return result
        if ($result['success']) {
            wp_send_json_success([
                'message' => __('Successfully connected to Geolocation API!', 'wp-woocommerce-printify-sync'),
                'data' => $result['data']
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['error'] ?? __('Failed to connect to Geolocation API.', 'wp-woocommerce-printify-sync')
            ]);
        }
    }
    
    /**
     * Test Currency API connection
     *
     * @return void
     */
    public function testCurrencyApi(): void
    {
        // Check nonce
        if (!$this->verifyAjaxNonce('wpps-ajax-nonce')) {
            return;
        }

        // Get API key from request
        $apiKey = sanitize_text_field($_POST['api_key'] ?? '');
        
        // Test API connection
        $currencyService = new CurrencyService($apiKey);
        $result = $currencyService->testConnection();
        
        // Return result
        if ($result['success']) {
            wp_send_json_success([
                'message' => __('Successfully connected to Currency API!', 'wp-woocommerce-printify-sync'),
                'data' => $result['data']
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['error'] ?? __('Failed to connect to Currency API.', 'wp-woocommerce-printify-sync')
            ]);
        }
    }
    
    /**
     * Save plugin settings
     *
     * @return void
     */
    public function saveSettings(): void
    {
        // Check nonce
        if (!$this->verifyAjaxNonce('wpps-ajax-nonce')) {
            return;
        }

        // Get settings from request
        $settings = [];
        
        if (isset($_POST['printify_api_key'])) {
            $settings['printify_api_key'] = sanitize_text_field($_POST['printify_api_key']);
        }
        
        if (isset($_POST['geolocation_api_key'])) {
            $settings['geolocation_api_key'] = sanitize_text_field($_POST['geolocation_api_key']);
        }
        
        if (isset($_POST['currency_api_key'])) {
            $settings['currency_api_key'] = sanitize_text_field($_POST['currency_api_key']);
        }
        
        if (isset($_POST['auto_sync_products'])) {
            $settings['auto_sync_products'] = (bool) $_POST['auto_sync_products'];
        }
        
        if (isset($_POST['sync_interval'])) {
            $settings['sync_interval'] = sanitize_text_field($_POST['sync_interval']);
        }
        
        // Save settings
        $result = update_option('wpps_settings', $settings);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Settings saved successfully!', 'wp-woocommerce-printify-sync')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to save settings.', 'wp-woocommerce-printify-sync')
            ]);
        }
    }
    
    /**
     * Sync products from Printify
     *
     * @return void
     */
    public function syncProducts(): void
    {
        // Check nonce
        if (!$this->verifyAjaxNonce('wpps-ajax-nonce')) {
            return;
        }

        // Get settings
        $settings = get_option('wpps_settings', []);
        $apiKey = $settings['printify_api_key'] ?? '';
        
        if (empty($apiKey)) {
            wp_send_json_error([
                'message' => __('Printify API key is not configured.', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        // Sync products
        $printifyService = new PrintifyService($apiKey);
        $result = $printifyService->syncProducts();
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => sprintf(
                    __('Successfully synced %d products from Printify!', 'wp-woocommerce-printify-sync'),
                    $result['count']
                )
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['error'] ?? __('Failed to sync products from Printify.', 'wp-woocommerce-printify-sync')
            ]);
        }
    }
    
    /**
     * Get shipping rates
     *
     * @return void
     */
    public function getShippingRates(): void
    {
        // Check nonce
        if (!$this->verifyAjaxNonce('wpps-frontend-nonce')) {
            return;
        }

        // Get product ID and country from request
        $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $country = sanitize_text_field($_POST['country'] ?? '');
        
        if (empty($productId) || empty($country)) {
            wp_send_json_error([
                'message' => __('Missing required parameters.', 'wp-woocommerce-printify-sync')
            ]);
            return;
        }
        
        // Get shipping rates
        $printifyService = new PrintifyService();
        $result = $printifyService->getShippingRates($productId, $country);
        
        if ($result['success']) {
            wp_send_json_success([
                'rates' => $result['rates']
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['error'] ?? __('Failed to get shipping rates.', 'wp-woocommerce-printify-sync')
            ]);
        }
    }
    
    /**
     * Get logs
     *
     * @return void
     */
    public function getLogs(): void
    {
        // Check nonce
        if (!$this->verifyAjaxNonce('wpps-ajax-nonce')) {
            return;
        }

        // Get log type from request
        $logType = sanitize_text_field($_POST['log_type'] ?? '');
        $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        $perPage = isset($_POST['per_page']) ? (int) $_POST['per_page'] : 20;
        
        // Get logs
        $logger = new Logger();
        $logs = $logger->getLogs($logType, $page, $perPage);
        
        wp_send_json_success([
            'logs' => $logs['items'],
            'total' => $logs['total'],
            'pages' => $logs['pages']
        ]);
    }
    
    /**
     * Verify AJAX nonce
     *
     * @param string $nonceAction The nonce action to verify
     * @return bool Whether nonce is valid
     */
    private function verifyAjaxNonce(string $nonceAction): bool
    {
        if (!check_ajax_referer($nonceAction, 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'wp-woocommerce-printify-sync')
            ]);
            return false;
        }
        
        return true;
    }
}