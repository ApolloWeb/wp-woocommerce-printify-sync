<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Repositories\ShippingRepository;

/**
 * Shipping Profile Service
 * 
 * Manages shipping profiles from Printify and integrates with WooCommerce shipping
 */
class ShippingProfileService {
    /**
     * @var PrintifyApiClient
     */
    private $api;
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var ShippingRepository
     */
    private $repository;
    
    /**
     * @var int Cache expiration in seconds (24 hours)
     */
    private $cache_expiration = 86400;
    
    /**
     * Constructor
     */
    public function __construct(
        PrintifyApiClient $api, 
        Logger $logger,
        ShippingRepository $repository = null
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->repository = $repository ?? new ShippingRepository();
        
        // Register hooks
        add_action('woocommerce_shipping_init', [$this, 'initShippingMethods']);
        add_filter('woocommerce_shipping_methods', [$this, 'addShippingMethods']);
        add_action('wp_ajax_wpwps_sync_shipping_profiles', [$this, 'syncShippingProfilesAjax']);
        
        // Handle shipping calculation at checkout
        add_filter('woocommerce_package_rates', [$this, 'adjustPackageRates'], 10, 2);
        add_action('woocommerce_cart_calculate_fees', [$this, 'calculateMultiProviderShipping']);
    }
    
    /**
     * Initialize shipping methods
     */
    public function initShippingMethods(): void {
        require_once WPPS_PATH . 'includes/Shipping/PrintifyShippingMethod.php';
    }
    
    /**
     * Add shipping methods to WooCommerce
     *
     * @param array $methods Existing shipping methods
     * @return array Modified shipping methods
     */
    public function addShippingMethods(array $methods): array {
        $methods['printify'] = 'ApolloWeb\\WPWooCommercePrintifySync\\Shipping\\PrintifyShippingMethod';
        return $methods;
    }
    
    /**
     * Sync shipping profiles from Printify
     *
     * @return bool Success
     */
    public function syncShippingProfiles(): bool {
        $this->logger->log('Syncing shipping profiles from Printify', 'info');
        
        try {
            // Get all print providers
            $providers = $this->api->getPrintProviders();
            
            if (empty($providers)) {
                throw new \Exception('No print providers returned from Printify API');
            }
            
            $profiles = [];
            
            // Get shipping profiles for each provider
            foreach ($providers as $provider) {
                $provider_id = $provider['id'];
                $provider_name = $provider['title'];
                
                $this->logger->log("Getting shipping profiles for provider: {$provider_name}", 'debug');
                
                $provider_profiles = $this->api->getProviderShippingProfiles($provider_id);
                
                if (!empty($provider_profiles)) {
                    foreach ($provider_profiles as $profile) {
                        $profile['provider_name'] = $provider_name;
                        $profile['provider_id'] = $provider_id;
                        $profiles[] = $profile;
                    }
                }
            }
            
            // Save profiles to the database
            $this->repository->saveShippingProfiles($profiles);
            
            // Cache profiles for faster access
            set_transient('wpwps_shipping_profiles', $profiles, $this->cache_expiration);
            
            // Create shipping zones and methods based on profiles
            $this->createShippingZones($profiles);
            
            $this->logger->log('Shipping profiles synced successfully', 'info');
            
            return true;
        } catch (\Exception $e) {
            $this->logger->log('Error syncing shipping profiles: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * AJAX handler for syncing shipping profiles
     */
    public function syncShippingProfilesAjax(): void {
        check_ajax_referer('wpps_admin');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
        }
        
        $success = $this->syncShippingProfiles();
        
        if ($success) {
            wp_send_json_success([
                'message' => __('Shipping profiles synced successfully', 'wp-woocommerce-printify-sync')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Error syncing shipping profiles', 'wp-woocommerce-printify-sync')
            ]);
        }
    }
    
    /**
     * Create WooCommerce shipping zones based on Printify profiles
     *
     * @param array $profiles Shipping profiles
     */
    private function createShippingZones(array $profiles): void {
        $zone_countries = [];
        
        // Group countries by zone
        foreach ($profiles as $profile) {
            if (empty($profile['countries'])) {
                continue;
            }
            
            foreach ($profile['countries'] as $country_data) {
                $country_code = $country_data['code'];
                
                if (!isset($zone_countries[$country_code])) {
                    $zone_countries[$country_code] = [
                        'name' => $country_data['name'],
                        'providers' => []
                    ];
                }
                
                if (!in_array($profile['provider_id'], $zone_countries[$country_code]['providers'])) {
                    $zone_countries[$country_code]['providers'][] = $profile['provider_id'];
                }
            }
        }
        
        // Create zones for each country
        foreach ($zone_countries as $country_code => $country_data) {
            $this->createOrUpdateShippingZone($country_code, $country_data);
        }
    }
    
    /**
     * Create or update a shipping zone for a country
     *
     * @param string $country_code Country code
     * @param array $country_data Country data
     */
    private function createOrUpdateShippingZone(string $country_code, array $country_data): void {
        $zone_name = sprintf('Printify - %s', $country_data['name']);
        
        // Check if zone already exists
        $existing_zone = $this->repository->getShippingZoneByName($zone_name);
        
        if ($existing_zone) {
            $zone = new \WC_Shipping_Zone($existing_zone->get_id());
        } else {
            $zone = new \WC_Shipping_Zone();
            $zone->set_zone_name($zone_name);
            $zone->save();
        }
        
        // Add country to zone
        $zone->add_location($country_code, 'country');
        
        // Add printify shipping method for this zone
        $this->addPrintifyShippingMethodToZone($zone, $country_data['providers']);
        
        $zone->save();
    }
    
    /**
     * Add Printify shipping method to a zone
     *
     * @param \WC_Shipping_Zone $zone Shipping zone
     * @param array $provider_ids Provider IDs
     */
    private function addPrintifyShippingMethodToZone(\WC_Shipping_Zone $zone, array $provider_ids): void {
        // Check if method already exists
        $method_exists = false;
        foreach ($zone->get_shipping_methods() as $method) {
            if ($method->id === 'printify') {
                $method_exists = true;
                break;
            }
        }
        
        // Add method if it doesn't exist
        if (!$method_exists) {
            $zone->add_shipping_method('printify');
        }
    }
    
    /**
     * Adjust package rates for Printify products
     *
     * @param array $rates Shipping rates
     * @param array $package Shipping package
     * @return array Modified rates
     */
    public function adjustPackageRates(array $rates, array $package): array {
        // Skip if there are no Printify products in the package
        if (!$this->packageContainsPrintifyProducts($package)) {
            return $rates;
        }
        
        // Group products by provider
        $providers = $this->getProvidersInPackage($package);
        
        // Modify rates based on provider shipping profiles
        foreach ($rates as $rate_id => $rate) {
            if (strpos($rate_id, 'printify') === false) {
                continue;
            }
            
            $total_cost = 0;
            
            // Calculate shipping for each provider
            foreach ($providers as $provider_id => $items) {
                $provider_cost = $this->calculateProviderShipping($provider_id, $items, $package['destination']);
                $total_cost += $provider_cost;
            }
            
            // Convert to store currency if WCML or CURCY is active
            $total_cost = $this->convertCurrency($total_cost);
            
            // Update rate cost
            $rates[$rate_id]->cost = $total_cost;
            
            // Update label to show multiple providers if needed
            if (count($providers) > 1) {
                $rates[$rate_id]->label = sprintf(
                    __('Printify Shipping (%d providers)', 'wp-woocommerce-printify-sync'),
                    count($providers)
                );
            }
        }
        
        return $rates;
    }
    
    /**
     * Calculate shipping for multiple providers and add as separate fees
     *
     * @param \WC_Cart $cart Cart object
     */
    public function calculateMultiProviderShipping(\WC_Cart $cart): void {
        // Skip if not in cart or checkout
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        // Get Printify products in cart
        $providers = $this->getProvidersInCart($cart);
        
        // Skip if no Printify products
        if (empty($providers)) {
            return;
        }
        
        // Skip if only one provider (standard shipping will work)
        if (count($providers) <= 1) {
            return;
        }
        
        // Get customer location
        $destination = $this->getCustomerDestination();
        
        // Add shipping fee for each provider
        foreach ($providers as $provider_id => $items) {
            $provider_name = $this->repository->getProviderName($provider_id);
            $cost = $this->calculateProviderShipping($provider_id, $items, $destination);
            
            // Convert to store currency
            $cost = $this->convertCurrency($cost);
            
            if ($cost > 0) {
                $cart->add_fee(
                    sprintf(__('Shipping: %s', 'wp-woocommerce-printify-sync'), $provider_name),
                    $cost,
                    true, // taxable
                    '' // tax class
                );
            }
        }
    }
    
    /**
     * Check if package contains Printify products
     *
     * @param array $package Shipping package
     * @return bool Whether package contains Printify products
     */
    private function packageContainsPrintifyProducts(array $package): bool {
        if (empty($package['contents'])) {
            return false;
        }
        
        foreach ($package['contents'] as $item) {
            $product = $item['data'];
            $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
            
            $printify_id = get_post_meta($product_id, '_printify_product_id', true);
            if (!empty($printify_id)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get providers in package grouped by provider ID
     *
     * @param array $package Shipping package
     * @return array Providers with items
     */
    private function getProvidersInPackage(array $package): array {
        $providers = [];
        
        foreach ($package['contents'] as $item) {
            $product = $item['data'];
            $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
            
            $provider_id = get_post_meta($product_id, '_printify_provider_id', true);
            
            if (empty($provider_id)) {
                continue;
            }
            
            if (!isset($providers[$provider_id])) {
                $providers[$provider_id] = [];
            }
            
            $providers[$provider_id][] = [
                'product_id' => $product_id,
                'variation_id' => $product->is_type('variation') ? $product->get_id() : 0,
                'quantity' => $item['quantity']
            ];
        }
        
        return $providers;
    }
    
    /**
     * Get providers in cart grouped by provider ID
     *
     * @param \WC_Cart $cart Cart object
     * @return array Providers with items
     */
    private function getProvidersInCart(\WC_Cart $cart): array {
        $providers = [];
        
        foreach ($cart->get_cart() as $item) {
            $product = $item['data'];
            $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
            
            $provider_id = get_post_meta($product_id, '_printify_provider_id', true);
            
            if (empty($provider_id)) {
                continue;
            }
            
            if (!isset($providers[$provider_id])) {
                $providers[$provider_id] = [];
            }
            
            $providers[$provider_id][] = [
                'product_id' => $product_id,
                'variation_id' => $product->is_type('variation') ? $product->get_id() : 0,
                'quantity' => $item['quantity']
            ];
        }
        
        return $providers;
    }
    
    /**
     * Calculate shipping cost for a provider
     *
     * @param string $provider_id Provider ID
     * @param array $items Items
     * @param array $destination Shipping destination
     * @return float Shipping cost
     */
    private function calculateProviderShipping(string $provider_id, array $items, array $destination): float {
        $country = $destination['country'] ?? '';
        
        if (empty($country)) {
            return 0;
        }
        
        // Get provider shipping profile
        $profile = $this->repository->getShippingProfileForProvider($provider_id, $country);
        
        if (empty($profile)) {
            return 0;
        }
        
        // Calculate total quantity
        $total_qty = 0;
        foreach ($items as $item) {
            $total_qty += $item['quantity'];
        }
        
        // Calculate cost using first_item + additional_item formula
        $first_item_cost = $profile['first_item'] ?? 0;
        $additional_item_cost = $profile['additional_item'] ?? 0;
        
        $cost = $first_item_cost;
        
        if ($total_qty > 1) {
            $cost += $additional_item_cost * ($total_qty - 1);
        }
        
        return $cost;
    }
    
    /**
     * Get customer shipping destination
     *
     * @return array Destination address
     */
    private function getCustomerDestination(): array {
        $destination = [];
        
        // Try to get from shipping address
        $customer = WC()->customer;
        
        if ($customer) {
            $destination = [
                'country' => $customer->get_shipping_country(),
                'state' => $customer->get_shipping_state(),
                'postcode' => $customer->get_shipping_postcode(),
                'city' => $customer->get_shipping_city()
            ];
        }
        
        // If missing country, use geolocation
        if (empty($destination['country'])) {
            $destination['country'] = $this->getGeolocatedCountry();
        }
        
        return $destination;
    }
    
    /**
     * Get geolocated country using MaxMind or WooCommerce default
     *
     * @return string Country code
     */
    private function getGeolocatedCountry(): string {
        // Use MaxMind if available
        if (class_exists('WC_Geolocation')) {
            $geolocation = \WC_Geolocation::geolocate_ip();
            return $geolocation['country'] ?? WC()->countries->get_base_country();
        }
        
        return WC()->countries->get_base_country();
    }
    
    /**
     * Convert currency if needed
     *
     * @param float $amount Amount in USD
     * @return float Amount in store currency
     */
    private function convertCurrency(float $amount): float {
        // If CURCY is active (Currency Switcher for WooCommerce)
        if (function_exists('wmc_get_price')) {
            return wmc_get_price($amount);
        }
        
        // If WCML is active (WooCommerce Multilingual)
        if (function_exists('wcml_convert_price')) {
            return wcml_convert_price($amount);
        }
        
        // Default: no conversion
        return $amount;
    }
    
    /**
     * Get cached shipping profiles
     *
     * @return array Shipping profiles
     */
    public function getCachedShippingProfiles(): array {
        $profiles = get_transient('wpwps_shipping_profiles');
        
        if (false === $profiles) {
            // Cache expired or doesn't exist, load from database
            $profiles = $this->repository->getAllShippingProfiles();
            
            // Recache if we have profiles
            if (!empty($profiles)) {
                set_transient('wpwps_shipping_profiles', $profiles, $this->cache_expiration);
            }
        }
        
        return $profiles ?: [];
    }
}
