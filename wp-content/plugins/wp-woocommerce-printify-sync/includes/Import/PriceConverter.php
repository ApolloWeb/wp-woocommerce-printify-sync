<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Import;

/**
 * Price Converter - Handles price conversions between Printify and WooCommerce
 * Adheres to Single Responsibility Principle by handling only price conversion logic
 */
class PriceConverter
{
    /**
     * Convert Printify price from minor units (cents) to decimal
     * 
     * @param int $priceInMinorUnits
     * @return float
     */
    public function convertFromMinorUnits(int $priceInMinorUnits): float
    {
        return floatval($priceInMinorUnits) / 100;
    }
    
    /**
     * Convert decimal price to minor units (cents)
     * 
     * @param float $price
     * @return int
     */
    public function convertToMinorUnits(float $price): int
    {
        return (int)round($price * 100);
    }
    
    /**
     * Format price according to WooCommerce settings
     * 
     * @param float $price
     * @return string
     */
    public function formatPrice(float $price): string
    {
        if (function_exists('wc_price')) {
            return wc_price($price);
        }
        
        return number_format($price, 2);
    }
}
