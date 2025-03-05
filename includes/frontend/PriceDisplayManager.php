<?php
/**
 * Price Display Manager - Handles dynamic currency display
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Frontend
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Frontend;

use ApolloWeb\WPWooCommercePrintifySync\Currency\CurrencyConverter;
use ApolloWeb\WPWooCommercePrintifySync\Geolocation\Geolocator;

class PriceDisplayManager {
    private static $instance = null;
    private $currency_converter;
    private $geolocator;
    private $shop_currency;
    private $display_currency;
    
    /**
     * Get single instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->currency_converter = CurrencyConverter::getInstance();
        $this->geolocator = Geolocator::getInstance();
        $this->shop_currency = get_option('woocommerce_currency', 'GBP');
        
        // Initialize
        add_action('wp_loaded', [$this, 'init']);
    }
    
    /**
     * Initialize hooks
     */
    public function init() {
        // Skip in admin
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }
        
        // Determine display currency
        $this->display_currency = $this->geolocator->getUserCurrency();
        
        // Only apply filters if display currency differs from shop currency
        if ($this->display_currency !== $this->shop_currency) {
            // Filter product prices for display
            add_filter('woocommerce_product_get_price', [$this, 'filterProductPrice'], 10, 2);
            add_filter('woocommerce_product_get_regular_price', [$this, 'filterProductPrice'], 10, 2);
            add_filter('woocommerce_product_get_sale_price', [$this, 'filterProductPrice'], 10, 2);
            
            // Filter variation prices
            add_filter('woocommerce_product_variation_get_price', [$this, 'filterProductPrice'], 10, 2);
            add_filter('woocommerce_product_variation_get_regular_price', [$this, 'filterProductPrice'], 10, 2);
            add_filter('woocommerce_product_variation_get_sale_price', [$this, 'filterProductPrice'], 10, 2);
            
            // Filter formatted prices
            add_filter('woocommerce_get_price_html', [$this, 'filterPriceHTML'], 10, 2);
            
            // Filter cart and order prices
            add_filter('woocommerce_cart_item_price', [$this, 'filterCartPrice'], 10, 3);
            add_filter('woocommerce_cart_item_subtotal', [$this, 'filterCartPrice'], 10, 3);
            add_filter('woocommerce_cart_subtotal', [$this, 'filterCartSubtotal'], 10, 3);
            add_filter('woocommerce_cart_total', [$this, 'filterCartTotal'], 10);
            
            // Change currency symbol
            add_filter('woocommerce_currency', [$this, 'changeCurrency']);
            
            // Add currency switcher
            add_action('wp_footer', [$this, 'addCurrencySwitcher']);
        }
        
        // Handle currency switching (AJAX)
        add_action('wp_ajax_wpwprintifysync_switch_currency', [$this, 'handleCurrencySwitch']);
        add_action('wp_ajax_nopriv_wpwprintifysync_switch_currency', [$this, 'handleCurrencySwitch']);
    }
    
    /**
     * Filter product price
     *
     * @param string $price Price
     * @param object $product WC_Product
     * @return string Filtered price
     */
    public function filterProductPrice($price, $product) {
        if (empty($price)) {
            return $price;
        }
        
        // Skip if this is a calculation for cart/checkout
        if (is_cart() || is_checkout() || is_admin() || wp_doing_cron()) {
            return $price;
        }
        
        // Convert to display currency
        return $this->currency_converter->convert(
            (float) $price,
            $this->shop_currency,
            $this->display_currency
        );
    }
    
    /**
     * Filter price HTML
     *
     * @param string $price_html Price HTML
     * @param object $product WC_Product
     * @return string Filtered price HTML
     */
    public function filterPriceHTML($price_html, $product) {
        if (empty($price_html)) {
            return $price_html;
        }
        
        // Add currency code to price
        $price_html .= ' <span class="wpwprintifysync-currency-code">(' . $this->display_currency . ')</span>';
        
        return $price_html;
    }
    
    /**
     * Filter cart price
     *
     * @param string $price_html Price HTML
     * @param array $cart_item Cart item
     * @param string $cart_item_key Cart item key
     * @return string Filtered price HTML
     */
    public function filterCartPrice($price_html, $cart_item, $cart_item_key) {
        // We don't convert cart prices - they stay in shop currency
        // But we can add currency info
        if (!empty($price_html)) {
            $price_html .= ' <span class="wpwprintifysync-currency-code">(' . $this->shop_currency . ')</span>';
        }
        
        return $price_html;
    }
    
    /**
     * Filter cart subtotal
     *
     * @param string $cart_subtotal Cart subtotal HTML
     * @param string $compound Is compound subtotal
     * @param object $cart WC_Cart
     * @return string Filtered cart subtotal HTML
     */
    public function filterCartSubtotal($cart_subtotal, $compound, $cart) {
        // Cart stays in shop currency
        if (!empty($cart_subtotal)) {
            $cart_subtotal .= ' <span class="wpwprintifysync-currency-code">(' . $this->shop_currency . ')</span>';
        }
        
        return $cart_subtotal;
    }
    
    /**
     * Filter cart total
     *
     * @param string $cart_total Cart total HTML
     * @return string Filtered cart total HTML
     */
    public function filterCartTotal($cart_total) {
        // Cart stays in shop currency
        if (!empty($cart_total)) {
            $cart_total .= ' <span class="wpwprintifysync-currency-code">(' . $this->shop_currency . ')</span>';
        }
        
        return $cart_total;
    }
    
    /**
     * Change currency
     *
     * @param string $currency Current currency
     * @return string New currency
     */
    public function changeCurrency($currency) {
        // Only change currency display outside cart/checkout
        if (!is_cart() && !is_checkout() && !is_admin() && !wp_doing_cron()) {
            return $this->display_currency;
        }
        
        return $currency;
    }
    
    /**
     * Add currency switcher to footer
     */
    public function addCurrencySwitcher() {
        $supported_currencies = $this->currency_converter->getSupportedCurrencies();
        $current_currency = $this->display_currency;
        
        ?>
        <div class="wpwprintifysync-currency-switcher">
            <span class="wpwprintifysync-currency-label"><?php _e('Select Currency:', 'wp-woocommerce-printify-sync'); ?></span>
            <select id="wpwprintifysync-currency-select">
                <?php foreach ($supported_currencies as $currency): ?>
                    <option value="<?php echo esc_attr($currency); ?>" <?php selected($currency, $current_currency); ?>>
                        <?php echo esc_html($currency); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <style>
            .wpwprintifysync-currency-switcher {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #fff;
                padding: 10px;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 9999;
                display: flex;
                align-items: center;
            }
            .wpwprintifysync-currency-label {
                margin-right: 10px;
                font-size: 14px;
            }
            .wpwprintifysync-currency-code {
                font-size: 0.8em;
                opacity: 0.7;
            }
        </style>
        
        <script>
            jQuery(document).ready(function($) {
                $('#wpwprintifysync-currency-select').on('change', function() {
                    var currency = $(this).val();
                    
                    // Show loading indicator
                    $('body').append('<div class="wpwprintifysync-loading">Changing currency...</div>');
                    
                    $.ajax({
                        url: wc_add_to_cart_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'wpwprintifysync_switch_currency',
                            currency: currency,
                            security: '<?php echo wp_create_nonce('wpwprintifysync-switch-currency'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Reload page to reflect new currency
                                window.location.reload();
                            } else {
                                alert('Error: ' + response.data);
                                $('.wpwprintifysync-loading').remove();
                            }
                        },
                        error: function() {
                            alert('Error changing currency. Please try again.');
                            $('.wpwprintifysync-loading