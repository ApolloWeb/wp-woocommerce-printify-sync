<?php
/**
 * Geolocator Interface
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Geolocation
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Geolocation;

interface GeolocatorInterface {
    /**
     * Get user's country based on IP
     *
     * @return string Country code (ISO 3166-1 alpha-2)
     */
    public function getUserCountry(): string;
    
    /**
     * Get user's preferred currency based on location
     *
     * @return string Currency code
     */
    public function getUserCurrency(): string;
    
    /**
     * Get currency code for country
     *
     * @param string $country_code Country code
     * @return string Currency code
     */
    public function getCurrencyForCountry(string $country_code): string;
}