<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Products\Helpers;

use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsServiceInterface;

/**
 * Helper class for handling product prices
 */
class PriceHandler {
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var SettingsServiceInterface
     */
    private $settings;
    
    /**
     * @var string
     */
    private $currency;
    
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param SettingsServiceInterface $settings
     */
    public function __construct(LoggerInterface $logger, SettingsServiceInterface $settings) {
        $this->logger = $logger;
        $this->settings = $settings;
        $this->currency = get_woocommerce_currency();
    }
    
    /**
     * Format price from Printify to WooCommerce
     *
     * @param float $price Printify price
     * @param string $from_currency Currency of Printify price (default USD)
     * @return float Formatted price in store currency
     */
    public function format_price($price, $from_currency = 'USD') {
        // Get price formatting settings
        $pricing_settings = $this->get_pricing_settings();
        
        // Convert currency if necessary
        if ($from_currency !== $this->currency) {
            $price = $this->convert_currency($price, $from_currency, $this->currency);
        }
        
        // Apply markup
        if (!empty($pricing_settings['markup_type']) && !empty($pricing_settings['markup_value'])) {
            $price = $this->apply_markup($price, $pricing_settings['markup_type'], $pricing_settings['markup_value']);
        }
        
        // Apply rounding
        if (!empty($pricing_settings['rounding_type']) && !empty($pricing_settings['rounding_value'])) {
            $price = $this->apply_rounding($price, $pricing_settings['rounding_type'], $pricing_settings['rounding_value']);
        }
        
        return $price;
    }
    
    /**
     * Convert price between currencies
     *
     * @param float $price Price to convert
     * @param string $from_currency Currency to convert from
     * @param string $to_currency Currency to convert to
     * @return float Converted price
     */
    public function convert_currency($price, $from_currency, $to_currency) {
        if ($from_currency === $to_currency) {
            return $price;
        }
        
        // Get exchange rates from settings
        $exchange_rates = $this->get_exchange_rates();
        
        // If we're converting from USD to GBP
        if ($from_currency === 'USD' && $to_currency === 'GBP') {
            $rate = !empty($exchange_rates['usd_to_gbp']) ? $exchange_rates['usd_to_gbp'] : 0.8;
            return $price * $rate;
        }
        
        // If we're converting from GBP to USD
        if ($from_currency === 'GBP' && $to_currency === 'USD') {
            $rate = !empty($exchange_rates['gbp_to_usd']) ? $exchange_rates['gbp_to_usd'] : 1.25;
            return $price * $rate;
        }
        
        // For other currencies, use default conversion rate
        $rate = $this->get_conversion_rate($from_currency, $to_currency);
        return $price * $rate;
    }
    
    /**
     * Apply markup to price
     *
     * @param float $price Price to markup
     * @param string $markup_type 'percentage' or 'fixed'
     * @param float $markup_value Markup amount
     * @return float Price with markup
     */
    public function apply_markup($price, $markup_type, $markup_value) {
        if ($markup_type === 'percentage') {
            return $price * (1 + ($markup_value / 100));
        } else { // fixed
            return $price + $markup_value;
        }
    }
    
    /**
     * Apply rounding to price
     *
     * @param float $price Price to round
     * @param string $rounding_type 'nearest', 'up', 'down'
     * @param float $rounding_value Value to round to
     * @return float Rounded price
     */
    public function apply_rounding($price, $rounding_type, $rounding_value) {
        if (empty($rounding_value) || $rounding_value <= 0) {
            return $price;
        }
        
        switch ($rounding_type) {
            case 'nearest':
                return round($price / $rounding_value) * $rounding_value;
                
            case 'up':
                return ceil($price / $rounding_value) * $rounding_value;
                
            case 'down':
                return floor($price / $rounding_value) * $rounding_value;
                
            case 'end_with_9':
                // Round up to the next whole number and subtract 0.01
                return ceil($price) - 0.01;
                
            case 'end_with_99':
                // Round up to the next whole number and subtract 0.01
                return ceil($price) - 0.01;
                
            default:
                return $price;
        }
    }
    
    /**
     * Get pricing settings from options
     *
     * @return array Pricing settings
     */
    public function get_pricing_settings() {
        $default_settings = [
            'markup_type' => 'percentage',
            'markup_value' => 50, // 50% markup by default
            'rounding_type' => 'end_with_99',
            'rounding_value' => 1,
            'min_price' => 9.99, // Minimum price
            'use_auto_pricing' => true // Whether to use automatic pricing
        ];
        
        $saved_settings = get_option('wpwps_pricing_settings', []);
        
        return wp_parse_args($saved_settings, $default_settings);
    }
    
    /**
     * Get exchange rates
     *
     * @return array Exchange rates
     */
    public function get_exchange_rates() {
        $default_rates = [
            'usd_to_gbp' => 0.8, // 1 USD = 0.8 GBP
            'gbp_to_usd' => 1.25, // 1 GBP = 1.25 USD
        ];
        
        $saved_rates = get_option('wpwps_exchange_rates', []);
        
        return wp_parse_args($saved_rates, $default_rates);
    }
    
    /**
     * Get conversion rate between two currencies
     *
     * @param string $from_currency Currency to convert from
     * @param string $to_currency Currency to convert to
     * @return float Conversion rate
     */
    private function get_conversion_rate($from_currency, $to_currency) {
        // Default to 1 if currencies are the same
        if ($from_currency === $to_currency) {
            return 1;
        }
        
        // For GBP specific conversions, use our stored rates
        $rates = $this->get_exchange_rates();
        
        $rate_key = strtolower($from_currency) . '_to_' . strtolower($to_currency);
        if (!empty($rates[$rate_key])) {
            return $rates[$rate_key];
        }
        
        // Default exchange rates for common currencies
        $default_rates = [
            'USD_to_EUR' => 0.92,
            'EUR_to_USD' => 1.09,
            'USD_to_CAD' => 1.35,
            'CAD_to_USD' => 0.74,
            'GBP_to_EUR' => 1.15,
            'EUR_to_GBP' => 0.87,
        ];
        
        $key = strtoupper($from_currency) . '_to_' . strtoupper($to_currency);
        
        if (isset($default_rates[$key])) {
            return $default_rates[$key];
        }
        
        // If we don't have a specific rate, use 1:1
        $this->logger->log_warning(
            'pricing',
            sprintf('No conversion rate found for %s to %s, using 1:1', $from_currency, $to_currency)
        );
        
        return 1;
    }
    
    /**
     * Set variable product price based on variations
     *
     * @param int $product_id WooCommerce product ID
     * @return bool Success
     */
    public function update_variable_product_prices($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product || !($product instanceof \WC_Product_Variable)) {
            return false;
        }
        
        // This updates the min/max prices
        $product->sync_price();
        
        // Force min/max price calculation
        $variation_prices = $product->get_variation_prices(true);
        
        // Save the product
        $product->save();
        
        return true;
    }
    
    /**
     * Format variation prices
     *
     * @param array $variations Array of variations
     * @param string $from_currency Currency of variation prices
     * @return array Updated variations with formatted prices
     */
    public function format_variation_prices($variations, $from_currency = 'USD') {
        if (empty($variations)) {
            return $variations;
        }
        
        foreach ($variations as $key => $variation) {
            if (!empty($variation['price'])) {
                $variations[$key]['price'] = $this->format_price($variation['price'], $from_currency);
            }
            
            if (!empty($variation['cost'])) {
                // Store original cost but don't apply markup
                $variations[$key]['original_cost'] = $variation['cost'];
                $variations[$key]['cost'] = $this->convert_currency($variation['cost'], $from_currency, $this->currency);
            }
        }
        
        return $variations;
    }
}
