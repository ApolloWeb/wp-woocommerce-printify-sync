<?php
/**
 * Shipping Zone Manager.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Shipping
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Manages WooCommerce shipping zones based on Printify profiles.
 */
class ShippingZoneManager {
    /**
     * ShippingProfileManager instance.
     *
     * @var ShippingProfileManager
     */
    private $profile_manager;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ShippingProfileManager $profile_manager ShippingProfileManager instance.
     * @param Logger                 $logger          Logger instance.
     */
    public function __construct(ShippingProfileManager $profile_manager, Logger $logger) {
        $this->profile_manager = $profile_manager;
        $this->logger = $logger;
    }

    /**
     * Initialize shipping zone manager.
     *
     * @return void
     */
    public function init() {
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_sync_shipping_zones', [$this, 'ajaxSyncShippingZones']);
        
        // Register custom shipping method
        add_action('woocommerce_shipping_init', [$this, 'registerShippingMethod']);
        add_filter('woocommerce_shipping_methods', [$this, 'addShippingMethod']);
    }

    /**
     * AJAX handler for syncing shipping zones.
     *
     * @return void
     */
    public function ajaxSyncShippingZones() {
        check_ajax_referer('wpwps_shipping_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
        }

        $result = $this->syncShippingZones();

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }

        wp_send_json_success([
            'message' => sprintf(
                __('Successfully synced %d shipping zones.', 'wp-woocommerce-printify-sync'),
                count($result)
            ),
            'zones' => $result,
        ]);
    }

    /**
     * Register Printify shipping method.
     *
     * @return void
     */
    public function registerShippingMethod() {
        if (!class_exists('PrintifyShippingMethod')) {
            include_once dirname(__FILE__) . '/PrintifyShippingMethod.php';
        }
    }

    /**
     * Add Printify shipping method to WooCommerce.
     *
     * @param array $methods Shipping methods.
     * @return array
     */
    public function addShippingMethod($methods) {
        $methods['printify_shipping'] = '\\ApolloWeb\\WPWooCommercePrintifySync\\Shipping\\PrintifyShippingMethod';
        return $methods;
    }

    /**
     * Sync shipping zones from Printify profiles.
     *
     * @return array|WP_Error
     */
    public function syncShippingZones() {
        $shipping_profiles = $this->profile_manager->getShippingProfiles();
        
        if (empty($shipping_profiles)) {
            return new \WP_Error(
                'no_shipping_profiles',
                __('No shipping profiles found. Please sync shipping profiles first.', 'wp-woocommerce-printify-sync')
            );
        }
        
        // Get all countries and regions that have shipping
        $shipping_locations = [];
        
        foreach ($shipping_profiles as $provider_id => $provider_data) {
            if (empty($provider_data['profiles']) || !is_array($provider_data['profiles'])) {
                continue;
            }
            
            foreach ($provider_data['profiles'] as $profile) {
                if (empty($profile['countries']) || !is_array($profile['countries'])) {
                    continue;
                }
                
                foreach ($profile['countries'] as $location) {
                    $country = $location['country'];
                    $region = isset($location['region']) ? $location['region'] : '';
                    
                    if (!isset($shipping_locations[$country])) {
                        $shipping_locations[$country] = [];
                    }
                    
                    if ($region && !in_array($region, $shipping_locations[$country])) {
                        $shipping_locations[$country][] = $region;
                    }
                }
            }
        }
        
        // Get existing shipping zones
        $zones = \WC_Shipping_Zones::get_zones();
        $existing_zones = [];
        
        foreach ($zones as $zone) {
            $zone_obj = new \WC_Shipping_Zone($zone['id']);
            $locations = $zone_obj->get_zone_locations();
            
            foreach ($locations as $location) {
                $key = $location->code;
                if ($location->type === 'state') {
                    $parts = explode(':', $location->code);
                    $key = $parts[0];
                    $subkey = isset($parts[1]) ? $parts[1] : '';
                    
                    if (!isset($existing_zones[$key])) {
                        $existing_zones[$key] = [
                            'zone_id' => $zone['id'],
                            'regions' => [],
                        ];
                    }
                    
                    if ($subkey) {
                        $existing_zones[$key]['regions'][] = $subkey;
                    }
                } else {
                    $existing_zones[$key] = [
                        'zone_id' => $zone['id'],
                        'regions' => [],
                    ];
                }
            }
        }
        
        // Create or update zones
        $created_zones = [];
        
        foreach ($shipping_locations as $country => $regions) {
            // Check if country zone exists
            if (!isset($existing_zones[$country])) {
                // Create new zone for country
                $country_name = \WC()->countries->countries[$country] ?? $country;
                $zone = $this->createShippingZone(
                    sprintf(__('Printify - %s', 'wp-woocommerce-printify-sync'), $country_name),
                    [
                        [
                            'type' => 'country',
                            'code' => $country,
                        ],
                    ]
                );
                
                if (!is_wp_error($zone)) {
                    $created_zones[] = [
                        'id' => $zone->get_id(),
                        'name' => $zone->get_zone_name(),
                        'country' => $country,
                    ];
                }
            } else {
                // Update existing zone - make sure it has our shipping method
                $zone_id = $existing_zones[$country]['zone_id'];
                $zone = new \WC_Shipping_Zone($zone_id);
                $this->addPrintifyShippingMethodToZone($zone);
            }
            
            // If this country has specific regions, create zones for them
            if (!empty($regions)) {
                foreach ($regions as $region) {
                    // Check if region already exists in a zone
                    if (isset($existing_zones[$country]['regions']) && in_array($region, $existing_zones[$country]['regions'])) {
                        continue;
                    }
                    
                    // Create region-specific zone
                    $region_name = \WC()->countries->states[$country][$region] ?? $region;
                    $zone = $this->createShippingZone(
                        sprintf(__('Printify - %s, %s', 'wp-woocommerce-printify-sync'), \WC()->countries->countries[$country] ?? $country, $region_name),
                        [
                            [
                                'type' => 'state',
                                'code' => $country . ':' . $region,
                            ],
                        ]
                    );
                    
                    if (!is_wp_error($zone)) {
                        $created_zones[] = [
                            'id' => $zone->get_id(),
                            'name' => $zone->get_zone_name(),
                            'country' => $country,
                            'region' => $region,
                        ];
                    }
                }
            }
        }
        
        // Create a catch-all zone for the rest of the world if it doesn't exist
        $rest_of_world = new \WC_Shipping_Zone(0);
        $shipping_methods = $rest_of_world->get_shipping_methods();
        $has_printify_method = false;
        
        foreach ($shipping_methods as $method) {
            if ($method->id === 'printify_shipping') {
                $has_printify_method = true;
                break;
            }
        }
        
        if (!$has_printify_method) {
            $rest_of_world->add_shipping_method('printify_shipping');
        }
        
        return $created_zones;
    }

    /**
     * Create a shipping zone.
     *
     * @param string $name      Zone name.
     * @param array  $locations Zone locations.
     * @return WC_Shipping_Zone|WP_Error
     */
    private function createShippingZone($name, $locations) {
        try {
            $zone = new \WC_Shipping_Zone(0);
            $zone->set_zone_name($name);
            $zone->save();
            
            // Add locations
            foreach ($locations as $location) {
                $zone->add_location($location['code'], $location['type']);
            }
            
            // Add Printify shipping method
            $this->addPrintifyShippingMethodToZone($zone);
            
            $zone->save();
            return $zone;
        } catch (\Exception $e) {
            return new \WP_Error('zone_creation_failed', $e->getMessage());
        }
    }

    /**
     * Add Printify shipping method to a zone.
     *
     * @param WC_Shipping_Zone $zone Shipping zone.
     * @return void
     */
    private function addPrintifyShippingMethodToZone($zone) {
        $shipping_methods = $zone->get_shipping_methods();
        $has_printify_method = false;
        
        foreach ($shipping_methods as $method) {
            if ($method->id === 'printify_shipping') {
                $has_printify_method = true;
                break;
            }
        }
        
        if (!$has_printify_method) {
            $zone->add_shipping_method('printify_shipping');
        }
    }
}
