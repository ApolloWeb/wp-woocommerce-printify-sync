<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ShippingService {
    private $api_service;
    private $shipping_method_id = 'wpwps_printify_shipping';

    public function __construct() {
        $this->api_service = new ApiService();
        
        add_action('woocommerce_shipping_init', [$this, 'initShippingMethod']);
        add_filter('woocommerce_shipping_methods', [$this, 'addShippingMethod']);
        add_action('wpwps_sync_shipping_profiles', [$this, 'syncShippingProfiles']);
    }

    public function initShippingMethod(): void {
        require_once WPWPS_PLUGIN_DIR . 'includes/Shipping/PrintifyShippingMethod.php';
    }

    public function addShippingMethod(array $methods): array {
        $methods[$this->shipping_method_id] = 'ApolloWeb\WPWooCommercePrintifySync\Shipping\PrintifyShippingMethod';
        return $methods;
    }

    public function syncShippingProfiles(): void {
        $response = $this->api_service->getShippingProfiles();
        
        if (!$response['success']) {
            do_action('wpwps_log_error', 'Failed to sync shipping profiles', $response);
            return;
        }

        $this->updateShippingZones($response['data']);
        do_action('wpwps_log_info', 'Shipping profiles synced', [
            'total_profiles' => count($response['data'])
        ]);
    }

    private function updateShippingZones(array $profiles): void {
        global $wpdb;

        // Get all existing shipping zones
        $zones = \WC_Shipping_Zones::get_zones();
        $zone_mappings = [];

        foreach ($profiles as $profile) {
            foreach ($profile['shipping_zones'] as $zone_data) {
                $zone_id = $this->getOrCreateZone($zone_data, $zones);
                $zone_mappings[$zone_id][] = [
                    'provider_id' => $profile['provider_id'],
                    'rates' => $zone_data['rates']
                ];
            }
        }

        // Update shipping methods for each zone
        foreach ($zone_mappings as $zone_id => $providers) {
            $zone = new \WC_Shipping_Zone($zone_id);
            
            // Remove existing Printify shipping methods
            foreach ($zone->get_shipping_methods() as $method) {
                if ($method->id === $this->shipping_method_id) {
                    $method->delete();
                }
            }

            // Add new shipping methods
            foreach ($providers as $provider) {
                $instance_id = $zone->add_shipping_method($this->shipping_method_id);
                
                // Configure the new method
                $method = \WC_Shipping_Zones::get_shipping_method($instance_id);
                $method->update_option('provider_id', $provider['provider_id']);
                $method->update_option('rates', $provider['rates']);
                $method->save();
            }
        }
    }

    private function getOrCreateZone(array $zone_data, array $existing_zones): int {
        // Try to find existing zone by country/region
        foreach ($existing_zones as $zone) {
            if ($this->zonesMatch($zone['zone_locations'], $zone_data['locations'])) {
                return $zone['id'];
            }
        }

        // Create new zone
        $zone = new \WC_Shipping_Zone();
        $zone->set_zone_name($zone_data['name']);
        
        // Add locations
        foreach ($zone_data['locations'] as $location) {
            $zone->add_location(
                $location['country'],
                $location['type'],
                isset($location['state']) ? $location['state'] : ''
            );
        }
        
        $zone->save();
        return $zone->get_id();
    }

    private function zonesMatch(array $zone1_locations, array $zone2_locations): bool {
        if (count($zone1_locations) !== count($zone2_locations)) {
            return false;
        }

        $locations1 = $this->normalizeLocations($zone1_locations);
        $locations2 = $this->normalizeLocations($zone2_locations);

        return count(array_diff($locations1, $locations2)) === 0;
    }

    private function normalizeLocations(array $locations): array {
        $normalized = [];
        
        foreach ($locations as $location) {
            $key = $location['type'] . ':' . $location['country'];
            if (isset($location['state'])) {
                $key .= ':' . $location['state'];
            }
            $normalized[] = $key;
        }

        sort($normalized);
        return $normalized;
    }

    public function calculateShippingForCart(array $rates, array $package): array {
        $items = $package['contents'];
        $providers = [];

        // Group items by provider
        foreach ($items as $item) {
            $product = $item['data'];
            $provider_id = get_post_meta($product->get_id(), '_printify_provider_id', true);
            
            if ($provider_id) {
                if (!isset($providers[$provider_id])) {
                    $providers[$provider_id] = [
                        'items' => [],
                        'total_quantity' => 0
                    ];
                }
                $providers[$provider_id]['items'][] = $item;
                $providers[$provider_id]['total_quantity'] += $item['quantity'];
            }
        }

        // Calculate shipping for each provider
        foreach ($providers as $provider_id => $data) {
            $method = $this->getShippingMethodForProvider($provider_id, $package);
            
            if ($method) {
                $rate = $method->calculate_shipping($data);
                if ($rate) {
                    $rates["printify_$provider_id"] = $rate;
                }
            }
        }

        return $rates;
    }

    private function getShippingMethodForProvider(string $provider_id, array $package): ?\WC_Shipping_Method {
        $shipping_zone = \WC_Shipping_Zones::get_zone_matching_package($package);
        
        foreach ($shipping_zone->get_shipping_methods() as $method) {
            if ($method->id === $this->shipping_method_id && $method->get_option('provider_id') === $provider_id) {
                return $method;
            }
        }

        return null;
    }

    public function getShippingRateForOrder(array $order_data): ?array {
        $rates = [];
        
        foreach ($order_data['line_items'] as $item) {
            $product_id = $this->getProductIdByPrintifyId($item['product_id']);
            if (!$product_id) continue;

            $provider_id = get_post_meta($product_id, '_printify_provider_id', true);
            if (!$provider_id) continue;

            if (!isset($rates[$provider_id])) {
                $rates[$provider_id] = [
                    'items' => [],
                    'total_quantity' => 0
                ];
            }
            
            $rates[$provider_id]['items'][] = $item;
            $rates[$provider_id]['total_quantity'] += $item['quantity'];
        }

        // Get shipping address
        $package = [
            'destination' => [
                'country' => $order_data['shipping_address']['country'],
                'state' => $order_data['shipping_address']['state'],
                'postcode' => $order_data['shipping_address']['zip']
            ]
        ];

        $shipping_costs = [];
        foreach ($rates as $provider_id => $data) {
            $method = $this->getShippingMethodForProvider($provider_id, $package);
            if ($method) {
                $rate = $method->calculate_shipping($data);
                if ($rate) {
                    $shipping_costs[] = $rate;
                }
            }
        }

        if (empty($shipping_costs)) {
            return null;
        }

        // Combine all shipping costs
        return [
            'cost' => array_sum(array_column($shipping_costs, 'cost')),
            'method' => 'Printify Shipping'
        ];
    }

    private function getProductIdByPrintifyId(string $printify_id): ?int {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' 
            AND meta_value = %s 
            LIMIT 1",
            $printify_id
        ));
        
        return $product_id ? (int) $product_id : null;
    }
}