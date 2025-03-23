<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ShippingZoneManager {
    private $api;
    private $logger;

    public function __construct(PrintifyApi $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
        
        add_action('wpps_sync_shipping_zones', [$this, 'syncZones']);
    }

    public function syncZones(int $provider_id): void {
        try {
            $shipping_profiles = $this->api->getPrintProviderShipping($provider_id);
            $this->createZonesFromProfiles($shipping_profiles);
        } catch (\Exception $e) {
            $this->logger->log("Failed to sync shipping zones: " . $e->getMessage(), 'error');
        }
    }

    private function createZonesFromProfiles(array $profiles): void {
        foreach ($profiles as $profile) {
            $zone = $this->findOrCreateZone($profile);
            $this->updateZoneMethods($zone, $profile);
        }
    }

    private function findOrCreateZone(array $profile): \WC_Shipping_Zone {
        $zones = \WC_Shipping_Zones::get_zones();
        foreach ($zones as $zone) {
            if ($zone['printify_profile_id'] === $profile['id']) {
                return new \WC_Shipping_Zone($zone['id']);
            }
        }

        $zone = new \WC_Shipping_Zone();
        $zone->set_zone_name($profile['name']);
        $zone->save();
        
        // Store Printify profile mapping
        update_option('wpps_zone_' . $zone->get_id() . '_profile', $profile['id']);
        
        return $zone;
    }

    private function updateZoneMethods(\WC_Shipping_Zone $zone, array $profile): void {
        // Clear existing methods
        foreach ($zone->get_shipping_methods() as $method) {
            $zone->delete_shipping_method($method->id);
        }

        // Add new methods based on profile
        foreach ($profile['rates'] as $rate) {
            $method = $zone->add_shipping_method('flat_rate');
            $method_instance = \WC_Shipping_Zones::get_shipping_method($method);
            
            if ($method_instance) {
                $method_instance->update_option('title', $rate['name']);
                $method_instance->update_option('cost', $rate['cost']);
                $method_instance->update_option('printify_rate_id', $rate['id']);
            }
        }
    }
}
