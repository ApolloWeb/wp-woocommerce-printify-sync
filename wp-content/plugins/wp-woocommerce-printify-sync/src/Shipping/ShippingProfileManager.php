<?php
/**
 * Shipping Profile Manager.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Shipping
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Manages shipping profiles from Printify.
 */
class ShippingProfileManager {
    /**
     * PrintifyAPI instance.
     *
     * @var PrintifyAPI
     */
    private $api;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Cache key for shipping profiles.
     *
     * @var string
     */
    private $cache_key = 'wpwps_shipping_profiles';

    /**
     * Cache expiration in seconds (24 hours).
     *
     * @var int
     */
    private $cache_expiration = 86400;

    /**
     * Constructor.
     *
     * @param PrintifyAPI $api    PrintifyAPI instance.
     * @param Logger      $logger Logger instance.
     */
    public function __construct(PrintifyAPI $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
    }

    /**
     * Initialize shipping profile manager.
     *
     * @return void
     */
    public function init() {
        add_action('wp_ajax_wpwps_sync_shipping_profiles', [$this, 'ajaxSyncShippingProfiles']);
        add_action('wpwps_daily_cron', [$this, 'syncShippingProfiles']);
    }

    /**
     * AJAX handler for syncing shipping profiles.
     *
     * @return void
     */
    public function ajaxSyncShippingProfiles() {
        check_ajax_referer('wpwps_shipping_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
        }

        $result = $this->syncShippingProfiles(true);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }

        wp_send_json_success([
            'message' => sprintf(
                __('Successfully synced shipping profiles for %d providers.', 'wp-woocommerce-printify-sync'),
                count($result)
            ),
            'profiles' => $result,
        ]);
    }

    /**
     * Sync shipping profiles from Printify.
     *
     * @param bool $force Force sync, ignore cache.
     * @return array|WP_Error
     */
    public function syncShippingProfiles($force = false) {
        // Check if we have cached data and not forcing a refresh
        if (!$force) {
            $cached = get_transient($this->cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Get all print providers
        $providers = $this->api->getPrintProviders();
        
        if (is_wp_error($providers)) {
            $this->logger->error(
                'Failed to get print providers.',
                ['error' => $providers->get_error_message()]
            );
            return $providers;
        }

        $all_shipping_profiles = [];

        // Get shipping profiles for each provider
        foreach ($providers as $provider) {
            $provider_id = $provider['id'];
            $shipping_profiles = $this->api->getShippingProfiles($provider_id);
            
            if (is_wp_error($shipping_profiles)) {
                $this->logger->error(
                    sprintf('Failed to get shipping profiles for provider %s.', $provider_id),
                    ['error' => $shipping_profiles->get_error_message()]
                );
                continue;
            }
            
            // Process profiles to ensure consistent format
            $processed_profiles = $this->processShippingProfiles($shipping_profiles, $provider);
            
            $all_shipping_profiles[$provider_id] = [
                'name' => $provider['title'],
                'profiles' => $processed_profiles,
                'last_updated' => current_time('timestamp'),
            ];
            
            // Update last updated time for this provider
            set_transient('wpwps_shipping_profiles_last_updated_' . $provider_id, current_time('timestamp'), $this->cache_expiration);
        }

        // Cache the results
        set_transient($this->cache_key, $all_shipping_profiles, $this->cache_expiration);
        
        // Also save as a regular option for permanent storage
        update_option('wpwps_shipping_profiles', $all_shipping_profiles);

        $this->logger->info(
            sprintf('Synced shipping profiles for %d providers.', count($all_shipping_profiles)),
            ['providers' => array_keys($all_shipping_profiles)]
        );

        return $all_shipping_profiles;
    }

    /**
     * Process shipping profiles to ensure consistent format.
     *
     * @param array $profiles Raw shipping profiles.
     * @param array $provider Provider data.
     * @return array
     */
    private function processShippingProfiles($profiles, $provider) {
        $processed = [];
        
        foreach ($profiles as $profile) {
            // Ensure consistent structure
            if (!isset($profile['shipping_methods']) || !is_array($profile['shipping_methods'])) {
                $profile['shipping_methods'] = [];
            }
            
            if (!isset($profile['countries']) || !is_array($profile['countries'])) {
                $profile['countries'] = [];
            }
            
            // Process shipping methods
            foreach ($profile['shipping_methods'] as &$method) {
                // Add provider info to each method
                $method['provider_id'] = $provider['id'];
                $method['provider_name'] = $provider['title'];
                
                // Ensure cost fields are present
                if (!isset($method['first_item'])) {
                    $method['first_item'] = isset($method['cost']) ? $method['cost'] : 0;
                }
                
                if (!isset($method['additional_items'])) {
                    $method['additional_items'] = isset($method['additional_cost']) ? $method['additional_cost'] : 0;
                }
            }
            
            $processed[] = $profile;
        }
        
        return $processed;
    }

    /**
     * Get cached shipping profiles.
     *
     * @param int|null $provider_id Specific provider ID or null for all.
     * @return array
     */
    public function getShippingProfiles($provider_id = null) {
        $profiles = get_transient($this->cache_key);
        
        // If no cache, try to get from permanent storage
        if ($profiles === false) {
            $profiles = get_option('wpwps_shipping_profiles', []);
            
            // If we have data in the option, cache it again
            if (!empty($profiles)) {
                set_transient($this->cache_key, $profiles, $this->cache_expiration);
            }
        }
        
        // If still empty or we need to force sync, get fresh data
        if (empty($profiles)) {
            $profiles = $this->syncShippingProfiles(true);
            
            // If we got an error, return empty array
            if (is_wp_error($profiles)) {
                return [];
            }
        }
        
        // Return specific provider profiles if requested
        if ($provider_id !== null) {
            return isset($profiles[$provider_id]) ? $profiles[$provider_id] : [];
        }
        
        return $profiles;
    }

    /**
     * Find shipping rate for a specific country/region.
     *
     * @param int    $provider_id Provider ID.
     * @param string $country     Country code.
     * @param string $state       State/region code.
     * @param string $postcode    Postal/ZIP code.
     * @return array|false
     */
    public function findShippingRate($provider_id, $country, $state = '', $postcode = '') {
        $provider_profiles = $this->getShippingProfiles($provider_id);
        
        if (empty($provider_profiles) || empty($provider_profiles['profiles'])) {
            return false;
        }
        
        $profiles = $provider_profiles['profiles'];
        
        // Look for exact country+state+postcode match first (for future support)
        if (!empty($postcode)) {
            foreach ($profiles as $profile) {
                if (isset($profile['postcodes']) && is_array($profile['postcodes'])) {
                    foreach ($profile['postcodes'] as $postcode_data) {
                        if ($postcode_data['country'] === $country && 
                            (empty($state) || $postcode_data['region'] === $state) &&
                            $this->postcodeMatches($postcode, $postcode_data['postcode'])) {
                            return [
                                'profile' => $profile,
                                'location' => $postcode_data,
                            ];
                        }
                    }
                }
            }
        }
        
        // Look for exact country+state match
        foreach ($profiles as $profile) {
            if (isset($profile['countries']) && is_array($profile['countries'])) {
                foreach ($profile['countries'] as $location) {
                    if ($location['country'] === $country) {
                        // Check if state matches or if we have a country-wide rate
                        if ((!empty($state) && !empty($location['region']) && $location['region'] === $state) || 
                            (empty($location['region']))) {
                            return [
                                'profile' => $profile,
                                'location' => $location,
                            ];
                        }
                    }
                }
            }
        }
        
        // Fall back to country match only
        foreach ($profiles as $profile) {
            if (isset($profile['countries']) && is_array($profile['countries'])) {
                foreach ($profile['countries'] as $location) {
                    if ($location['country'] === $country && empty($location['region'])) {
                        return [
                            'profile' => $profile,
                            'location' => $location,
                        ];
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Check if postcode matches pattern (for future support).
     *
     * @param string $postcode Postcode to check.
     * @param string $pattern  Pattern to match against.
     * @return bool
     */
    private function postcodeMatches($postcode, $pattern) {
        // For exact match
        if ($postcode === $pattern) {
            return true;
        }
        
        // For wildcards
        if (strpos($pattern, '*') !== false) {
            $regex = '/^' . str_replace('*', '.*', $pattern) . '$/i';
            return (bool) preg_match($regex, $postcode);
        }
        
        // For ranges (e.g., 10000-20000)
        if (strpos($pattern, '-') !== false) {
            list($min, $max) = explode('-', $pattern);
            if (is_numeric($min) && is_numeric($max) && is_numeric($postcode)) {
                return $postcode >= $min && $postcode <= $max;
            }
        }
        
        return false;
    }

    /**
     * Calculate shipping cost for items.
     *
     * @param int   $provider_id Provider ID.
     * @param array $items       Cart items with quantities.
     * @param array $destination Shipping destination.
     * @return array|false
     */
    public function calculateShippingCost($provider_id, $items, $destination) {
        // Find shipping rate for the destination
        $rate = $this->findShippingRate(
            $provider_id, 
            $destination['country'], 
            isset($destination['state']) ? $destination['state'] : '',
            isset($destination['postcode']) ? $destination['postcode'] : ''
        );
        
        if (!$rate) {
            return false;
        }
        
        $shipping_methods = [];
        
        // Calculate cost for each shipping method
        if (isset($rate['profile']['shipping_methods']) && is_array($rate['profile']['shipping_methods'])) {
            foreach ($rate['profile']['shipping_methods'] as $method) {
                $total_quantity = 0;
                foreach ($items as $item) {
                    $total_quantity += $item['quantity'];
                }
                
                // We'll just pass the raw data and let the shipping method handle the calculation
                // based on the selected approach (first_item + additional_items or flat)
                $shipping_methods[] = [
                    'id' => $method['id'],
                    'name' => $method['name'],
                    'carrier' => isset($method['carrier']) ? $method['carrier'] : '',
                    'cost' => isset($method['cost']) ? $method['cost'] : $method['first_item'],
                    'first_item' => isset($method['first_item']) ? $method['first_item'] : 0,
                    'additional_items' => isset($method['additional_items']) ? $method['additional_items'] : 0,
                    'min_delivery_days' => isset($method['min_delivery_days']) ? $method['min_delivery_days'] : null,
                    'max_delivery_days' => isset($method['max_delivery_days']) ? $method['max_delivery_days'] : null,
                ];
            }
        }
        
        // Convert currency for all methods
        foreach ($shipping_methods as &$method) {
            $method['cost'] = $this->convertCurrency($method['cost']);
            $method['first_item'] = $this->convertCurrency($method['first_item']);
            $method['additional_items'] = $this->convertCurrency($method['additional_items']);
        }
        
        return [
            'provider_id' => $provider_id,
            'provider_name' => $this->getShippingProfiles($provider_id)['name'],
            'methods' => $shipping_methods,
        ];
    }

    /**
     * Convert currency from USD to store currency if needed.
     *
     * @param float $amount Amount in USD.
     * @return float
     */
    private function convertCurrency($amount) {
        // Check if CURCY plugin is active and if store currency is not USD
        if (function_exists('wmc_get_price') && get_woocommerce_currency() !== 'USD') {
            return wmc_get_price($amount);
        }
        
        return floatval($amount);
    }
}
