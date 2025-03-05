<?php
/**
 * Currency Switcher
 *
 * Handles frontend currency switching functionality.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Frontend
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Frontend;

use ApolloWeb\WPWooCommercePrintifySync\Currency\CurrencyConverter;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CurrencySwitcher {
    /**
     * Singleton instance
     *
     * @var CurrencySwitcher
     */
    private static $instance = null;
    
    /**
     * Available currencies
     *
     * @var array
     */
    private $currencies = array();
    
    /**
     * Current currency
     *
     * @var string
     */
    private $current_currency = '';
    
    /**
     * Get singleton instance
     *
     * @return CurrencySwitcher
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->currencies = array(
            'USD' => array(
                'name' => __('US Dollar', 'wp-woocommerce-printify-sync'),
                'symbol' => '$'
            ),
            'EUR' => array(
                'name' => __('Euro', 'wp-woocommerce-printify-sync'),
                'symbol' => '€'
            ),
            'GBP' => array(
                'name' => __('British Pound', 'wp-woocommerce-printify-sync'),
                'symbol' => '£'
            ),
            'CAD' => array(
                'name' => __('Canadian Dollar', 'wp-woocommerce-printify-sync'),
                'symbol' => 'C$'
            ),
            'AUD' => array(
                'name' => __('Australian Dollar', 'wp-woocommerce-printify-sync'),
                'symbol' => 'A$'
            ),
        );
        
        // Get current currency from session or default
        $settings = get_option('wpwprintifysync_settings', array());
        $this->current_currency = isset($_COOKIE['wpwprintifysync_currency']) ? sanitize_text_field($_COOKIE['wpwprintifysync_currency']) : ($settings['default_currency'] ?? 'USD');
        
        // Validate currency
        if (!array_key_exists($this->current_currency, $this->currencies)) {
            $this->current_currency = 'USD';
        }
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Add hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_before_shop_loop', array($this, 'render_currency_switcher'));
        add_action('woocommerce_before_single_product', array($this, 'render_currency_switcher'));
        add_action('wp_footer', array($this, 'render_currency_switcher_mobile'));
        add_action('wp_ajax_wpwprintifysync_switch_currency', array($this, 'ajax_switch_currency'));
        add_action('wp_ajax_nopriv_wpwprintifysync_switch_currency', array($this, 'ajax_switch_currency'));
        
        // Filter prices
        add_filter('woocommerce_product_get_price', array($this, 'convert_price'), 10, 2);
        add_filter('woocommerce_product_get_regular_price', array($this, 'convert_price'), 10, 2);
        add_filter('woocommerce_product_get_sale_price', array($this, 'convert_price'), 10, 2);
        add_filter('woocommerce_product_variation_get_price', array($this, 'convert_price'), 10, 2);
        add_filter('woocommerce_product_variation_get_regular_price', array($this, 'convert_price'), 10, 2);
        add_filter('woocommerce_product_variation_get_sale_price', array($this, 'convert_price'), 10, 2);
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style('wpwprintifysync-frontend', WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/css/frontend.css', array(), WPWPRINTIFYSYNC_VERSION);
        wp_enqueue_script('wpwprintifysync-frontend', WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WPWPRINTIFYSYNC_VERSION, true);
        
        wp_localize_script('wpwprintifysync-frontend', 'wpwprintifysync', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwprintifysync-frontend-nonce'),
        ));
    }
    
    /**
     * Render currency switcher
     */
    public function render_currency_switcher() {
        $settings = get_option('wpwprintifysync_settings', array());
        
        if (!isset($settings['enable_multi_currency']) || $settings['enable_multi_currency'] !== 'yes') {
            return;
        }
        
        $available_currencies = isset($settings['available_currencies']) ? $settings['available_currencies'] : array('USD');
        
        echo '<div class="wpwprintifysync-currency-switcher">';
        echo '<span class="label">' . esc_html__('Currency:', 'wp-woocommerce-printify-sync') . '</span>';
        echo '<select name="currency" class="currency-select">';
        
        foreach ($this->currencies as $code => $currency) {
            if (in_array($code, $available_currencies)) {
                echo '<option value="' . esc_attr($code) . '" ' . selected($this->current_currency, $code, false) . '>';
                echo esc_html($currency['name']) . ' (' . esc_html($currency['symbol']) . ')';
                echo '</option>';
            }
        }
        
        echo '</select>';
        echo '</div>';
    }
    
    /**
     * Render mobile currency switcher
     */
    public function render_currency_switcher_mobile() {
        $settings = get_option('wpwprintifysync_settings', array());
        
        if (!isset($settings['enable_multi_currency']) || $settings['enable_multi_currency'] !== 'yes') {
            return;
        }
        
        echo '<div class="wpwprintifysync-currency-switcher-mobile">';
        $this->render_currency_switcher();
        echo '</div>';
    }
    
    /**
     * AJAX handler for currency switching
     */
    public function ajax_switch_currency() {
        check_ajax_referer('wpwprintifysync-frontend-nonce', 'nonce');
        
        $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'USD';
        
        // Validate currency
        if (!array_key_exists($currency, $this->currencies)) {
            $currency = 'USD';
        }
        
        // Set cookie
        setcookie('wpwprintifysync_currency', $currency, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        
        wp_send_json_success(array(
            'message' => __('Currency switched successfully', 'wp-woocommerce-printify-sync'),
            'currency' => $currency
        ));
    }
    
    /**
     * Convert price based on current currency
     *
     * @param float $price Product price
     * @param object $product WC_Product
     * @return float Converted price
     */
    public function convert_price($price, $product) {
        if (!$price) {
            return $price;
        }
        
        $settings = get_option('wpwprintifysync_settings', array());
        
        if (!isset($settings['enable_multi_currency']) || $settings['enable_multi_currency'] !== 'yes') {
            return $price;
        }
        
        $base_currency = $settings['default_currency'] ?? 'USD';
        
        if ($this->current_currency === $base_currency) {
            return $price;
        }
        
        // Get currency converter
        $currency_converter = CurrencyConverter::get_instance();
        
        // Convert price
        $converted_price = $currency_converter->convert($price, $base_currency, $this->current_currency);
        
        return $converted_price;
    }
}