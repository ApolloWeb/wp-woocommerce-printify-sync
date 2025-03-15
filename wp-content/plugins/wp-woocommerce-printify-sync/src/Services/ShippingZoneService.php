<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ShippingZoneService
{
    private string $currentTime = '2025-03-15 19:01:40';
    private string $currentUser = 'ApolloWeb';
    private PrintifyApi $printifyApi;

    public function __construct()
    {
        $this->printifyApi = new PrintifyApi(
            get_option('wpwps_printify_api_key'),
            get_option('wpwps_printify_endpoint')
        );

        add_action('wp_ajax_wpwps_sync_shipping_zones', [$this, 'syncShippingZones']);
        add_action('woocommerce_shipping_zone_method_added', [$this, 'handleZoneMethodAdded']);
    }

    public function syncShippingZones(string $shopId): array
    {
        try {
            // Get Printify shipping methods
            $printifyMethods = $this->printifyApi->getShippingMethods($shopId);
            
            // Get WooCommerce zones
            $zones = \WC_Shipping_Zones::get_zones();
            
            foreach ($printifyMethods as $method) {
                $this->createOrUpdateZone($method, $zones);
            }

            return [
                'success' => true,
                'message' => __('Shipping zones synchronized successfully', 'wp-woocommerce-printify-sync')
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function createOrUpdateZone(array $printifyMethod, array $existingZones): void
    {
        // Check if zone exists
        $zoneExists = false;
        foreach ($existingZones as $zone) {
            if ($zone['zone_name'] === $printifyMethod['name']) {
                $zoneExists = true;
                $this->updateZoneRates($zone['id'], $printifyMethod);
                break;
            }
        }

        // Create new zone if it doesn't exist
        if (!$zoneExists) {
            $zone = $this->createZone($printifyMethod);
            $this->addZoneLocations($zone->get_id(), $printifyMethod['locations']);
            $this->addZoneShippingMethods($zone->get_id(), $printifyMethod['rates']);
        }
    }

    private function createZone(array $printifyMethod): \WC_Shipping_Zone
    {
        $zone = new \WC_Shipping_Zone();
        $zone->set_zone_name($printifyMethod['name']);
        $zone->save();

        return $zone;
    }

    private function addZoneLocations(int $zoneId, array $locations): void
    {
        $zone = \WC_Shipping_Zones::get_zone($zoneId);
        $zoneLocations = [];

        foreach ($locations as $location) {
            $zoneLocations[] = [
                'type' => 'country',
                'code' => $location['country_code']
            ];
        }

        $zone->set_locations($zoneLocations);
        $zone->save();
    }

    private function addZoneShippingMethods(int $zoneId, array $rates): void
    {
        $zone = \WC_Shipping_Zones::get_zone($zoneId);

        foreach ($rates as $rate) {
            $methodId = $zone->add_shipping_method('printify_calculated');
            $method = \WC_Shipping_Zones::get_shipping_method($methodId);

            if ($method) {
                $method->update_option('title', $rate['name']);
                $method->update_option('cost', $rate['cost']);
                $method->update_option('printify_rate_id', $rate['id']);
            }
        }
    }

    private function updateZoneRates(int $zoneId, array $printifyMethod): void
    {
        $zone = \WC_Shipping_Zones::get_zone($zoneId);
        $methods = $zone->get_shipping_methods();

        // Remove existing methods
        foreach ($methods as $method) {
            $zone->delete_shipping_method($method->id);
        }

        // Add updated methods
        $this->addZoneShippingMethods($zoneId, $printifyMethod['rates']);
    }
}