    /**
     * Get supported currencies
     *
     * @return array Supported currencies
     */
    public function get_supported_currencies() {
        return $this->supported_currencies;
    }
    
    /**
     * Get exchange rates
     *
     * @return array Exchange rates
     */
    public function get_exchange_rates() {
        return $this->exchange_rates;
    }
    
    /**
     * Convert amount from one currency to another
     *
     * @param float $amount Amount to convert
     * @param string $from_currency Source currency code
     * @param string $to_currency Target currency code
     * @return float Converted amount
     */
    public function convert_amount($amount, $from_currency, $to_currency) {
        if ($from_currency === $to_currency) {
            return $amount;
        }
        
        if (empty($this->exchange_rates['rates'][$from_currency]) || empty($this->exchange_rates['rates'][$to_currency])) {
            return $amount;
        }
        
        // Convert to base currency first
        $base_amount = $amount / $this->exchange_rates['rates'][$from_currency];
        
        // Then convert to target currency
        $converted_amount = $base_amount * $this->exchange_rates['rates'][$to_currency];
        
        // Round according to WooCommerce settings
        $precision = get_option('woocommerce_price_num_decimals', 2);
        return round($converted_amount, $precision);
    }
    
    /**
     * Set the current currency
     *
     * @param string $currency Currency code
     */
    public function set_current_currency($currency) {
        if (isset($this->supported_currencies[$currency])) {
            $this->current_currency = $currency;
            
            // Store in session
            if (!session_id()) {
                session_start();
            }
            $_SESSION['wpwprintifysync_currency'] = $currency;
        }
    }
    
    /**
     * Get currency symbol
     *
     * @param string $currency Currency code
     * @return string Currency symbol
     */
    public function get_currency_symbol($currency = '') {
        if (empty($currency)) {
            $currency = $this->get_current_currency();
        }
        
        if (isset($this->supported_currencies[$currency]['symbol'])) {
            return $this->supported_currencies[$currency]['symbol'];
        }
        
        // Fallback to WooCommerce's method
        return get_woocommerce_currency_symbol($currency);
    }
    
    /**
     * Format price with currency symbol
     *
     * @param float $price Price to format
     * @param string $currency Currency code
     * @return string Formatted price with currency symbol
     */
    public function format_price_with_currency($price, $currency = '') {
        if (empty($currency)) {
            $currency = $this->get_current_currency();
        }
        
        $symbol = $this->get_currency_symbol($currency);
        $precision = get_option('woocommerce_price_num_decimals', 2);
        $price = number_format($price, $precision, wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
        
        $format = get_option('woocommerce_currency_pos');
        
        switch ($format) {
            case 'left':
                return $symbol . $price;
            case 'right':
                return $price . $symbol;
            case 'left_space':
                return $symbol . ' ' . $price;
            case 'right_space':
                return $price . ' ' . $symbol;
            default:
                return $symbol . $price;
        }
    }
}