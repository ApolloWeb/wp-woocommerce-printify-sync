<?php
/**
 * Format Helper Class
 *
 * Helper functions for formatting data
 *
 * @package WP_WooCommerce_Printify_Sync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

defined('ABSPATH') || exit;

/**
 * Format Helper class
 */
class FormatHelper {
    
    /**
     * Format price with currency symbol
     *
     * @param float  $price   Price value.
     * @param string $currency Currency code (optional).
     * @return string Formatted price
     */
    public static function format_price($price, $currency = '') {
        if (empty($currency)) {
            return wc_price($price);
        }
        
        return wc_price($price, ['currency' => $currency]);
    }
    
    /**
     * Format a date in the site's timezone
     * 
     * @param string $date Date string or timestamp.
     * @param string $format PHP date format (optional).
     * @return string Formatted date
     */
    public static function format_date($date, $format = '') {
        if (empty($format)) {
            $format = get_option('date_format');
        }
        
        if (is_numeric($date)) {
            $timestamp = $date;
        } else {
            $timestamp = strtotime($date);
        }
        
        return date_i18n($format, $timestamp);
    }
    
    /**
     * Clean string for use in slugs or IDs
     *
     * @param string $string Input string.
     * @return string Sanitized string
     */
    public static function sanitize_key($string) {
        return sanitize_title($string);
    }
    
    /**
     * Convert array to comma-separated string
     *
     * @param array $array Input array.
     * @return string Comma-separated list
     */
    public static function array_to_string($array) {
        if (!is_array($array)) {
            return '';
        }
        
        return implode(', ', $array);
    }
}