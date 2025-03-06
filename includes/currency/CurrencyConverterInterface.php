<?php
/**
 * Currency Converter Interface
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Currency
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Currency;

interface CurrencyConverterInterface {
    /**
     * Convert amount from one currency to another
     *
     * @param float $amount Amount to convert
     * @param string $from_currency Source currency code
     * @param string $to_currency Target currency code
     * @return float Converted amount
     */
    public function convert(float $amount, string $from_currency, string $to_currency): float;
    
    /**
     * Get exchange rate between currencies
     *
     * @param string $from_currency Source currency code
     * @param string $to_currency Target currency code
     * @return float Exchange rate
     */
    public function getExchangeRate(string $from_currency, string $to_currency): float;
    
    /**
     * Update exchange rates from API
     *
     * @param bool $force Force update even if cache is valid
     * @return bool Success status
     */
    public function updateExchangeRates(bool $force = false): bool;
    
    /**
     * Get supported currencies
     *
     * @return array List of supported currency codes
     */
    public function getSupportedCurrencies(): array;
}