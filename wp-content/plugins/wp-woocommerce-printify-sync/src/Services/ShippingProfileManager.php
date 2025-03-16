<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class ShippingProfileManager extends AbstractService
{
    private PrintifyAPI $api;
    private array $cachedProfiles = [];

    public function __construct(
        PrintifyAPI $api,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        parent::__construct($logger, $config);
        $this->api = $api;
    }

    public function syncShippingProfiles(): void
    {
        try {
            // Fetch profiles from Printify
            $profiles = $this->api->getShipping();
            
            // Store profiles in WP options
            update_option('printify_shipping_profiles', $profiles);
            update_option('printify_shipping_last_sync', $this->getCurrentTime());

            // Create WooCommerce shipping zones and methods
            $this->createShippingZones($profiles);

            $this->logOperation('syncShippingProfiles', [
                'message' => 'Shipping profiles synchronized',
                'profile_count' => count($profiles)
            ]);

        } catch (\Exception $e) {
            $this->logError('syncShippingProfiles', $e);
        }
    }

    private function createShippingZones(array $profiles): void
    {
        foreach ($profiles as $profile) {
            $this->createOrUpdateShippingZone($profile);
        }
    }

    private function createOrUpdateShippingZone(array $profile): void
    {
        global $wpdb;

        try {
            // Check if zone exists
            $zoneName = "Printify - {$profile['name']}";
            $zoneId = $wpdb->get_var($wpdb->prepare(
                "SELECT zone_id FROM {$wpdb->prefix}woocommerce_shipping_zones 
                WHERE zone_name = %s",
                $zoneName
            ));

            // Create or update zone
            if (!$zoneId) {
                $zone = new \WC_Shipping_Zone();
                $zone->set_zone_name($zoneName);
                $zone->save();
                $zoneId = $zone->get_id();
            } else {
                $zone = new \WC_Shipping_Zone($zoneId);
            }

            // Add locations to zone
            $this->addLocationsToZone($zone, $profile['locations']);

            // Add shipping methods
            $this->addShippingMethods($zone, $profile['methods']);

            $this->logOperation('createOrUpdateShippingZone', [
                'zone_id' => $zoneId,
                'profile_id' => $profile['id']
            ]);

        } catch (\Exception $e) {
            $this->logError('createOrUpdateShippingZone', $e, [
                'profile' => $profile
            ]);
        }
    }

    private function addLocationsToZone(\WC_Shipping_Zone $zone, array $locations): void
    {
        $zoneLocations = [];

        foreach ($locations as $location) {
            if (isset($location['country_code'])) {
                if (isset($location['state_code'])) {
                    // State-specific location
                    $zoneLocations[] = [
                        'code' => $location['country_code'] . ':' . $location['state_code'],
                        'type' => 'state'
                    ];
                } else {
                    // Country-wide location
                    $zoneLocations[] = [
                        'code' => $location['country_code'],
                        'type' => 'country'
                    ];
                }
            }
        }

        $zone->set_locations($zoneLocations);
        $zone->save();
    }

    private function addShippingMethods(\WC_Shipping_Zone $zone, array $methods): void
    {
        // Remove existing methods
        foreach ($zone->get_shipping_methods() as $method) {
            $zone->delete_shipping_method($method->id);
        }

        // Add new methods
        foreach ($methods as $method) {
            $instance = $zone->add_shipping_method('printify_shipping');
            
            if ($instance) {
                $methodSettings = [
                    'title' => $method['name'],
                    'cost' => $method['base_cost'],
                    'printify_method_id' => $method['id'],
                    'delivery_time' => $method['delivery_time'],
                    'handling_time' => $method['handling_time']
                ];

                // Add any variant-specific costs
                if (!empty($method['variant_costs'])) {
                    $methodSettings['variant_costs'] = $method['variant_costs'];
                }

                update_option(
                    'woocommerce_printify_shipping_' . $instance . '_settings',
                    $methodSettings
                );
            }
        }
    }

    public function calculateShippingCosts(
        array $package,
        string $methodId
    ): ?array {
        try {
            // Get method settings
            $settings = $this->getMethodSettings($methodId);
            if (!$settings) {
                return null;
            }

            // Calculate base cost
            $cost = $settings['cost'];

            // Add variant-specific costs
            foreach ($package['contents'] as $item) {
                $variantId = $this->getProductVariantId($item['product_id']);
                if ($variantId && isset($settings['variant_costs'][$variantId])) {
                    $cost += $settings['variant_costs'][$variantId] * $item['quantity'];
                }
            }

            return [
                'cost' => $cost,
                'delivery_time' => $settings['delivery_time'],
                'handling_time' => $settings['handling_time']
            ];

        } catch (\Exception $e) {
            $this->logError('calculateShippingCosts', $e, [
                'package' => $package,
                'method_id' => $methodId
            ]);
            return null;
        }
    }

    private function getMethodSettings(string $methodId): ?array
    {
        return get_option('woocommerce_printify_shipping_' . $methodId . '_settings');
    }

    private function getProductVariantId($productId): ?string
    {
        return get_post_meta($productId, '_printify_variant_id', true);
    }

    public function refreshShippingCache(): void
    {
        $this->cachedProfiles = [];
        wp_cache_delete('printify_shipping_profiles', 'options');
        $this->syncShippingProfiles();
    }
}