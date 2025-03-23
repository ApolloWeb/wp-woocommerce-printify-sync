<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ShippingRateManager {
    private $api;
    private $logger;
    private $zone_patterns = [
        'domestic' => [
            'countries' => ['US'],
            'priority' => 10
        ],
        'international' => [
            'countries' => 'all',
            'priority' => 20
        ],
        'europe' => [
            'continents' => ['EU'],
            'priority' => 15
        ]
    ];

    public function __construct(PrintifyApi $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;

        add_action('wpps_sync_shipping_rates', [$this, 'syncRates']);
        add_filter('woocommerce_shipping_zone_shipping_methods', [$this, 'addPrintifyRates'], 10, 4);
    }

    public function syncRates(int $provider_id): void {
        try {
            $profiles = $this->api->getPrintProviderShipping($provider_id);
            $this->createZonesFromProfiles($profiles);
            
            foreach ($profiles as $profile) {
                $this->syncProfileRates($profile);
            }
        } catch (\Exception $e) {
            $this->logger->log("Failed to sync shipping rates: " . $e->getMessage(), 'error');
        }
    }

    private function syncProfileRates(array $profile): void {
        foreach ($profile['shipping_rates'] as $rate) {
            $zone = $this->getZoneForRate($rate);
            if (!$zone) continue;

            $this->updateZoneMethod($zone, [
                'title' => $rate['name'],
                'cost' => $rate['rate'],
                'printify_rate_id' => $rate['id'],
                'calculation_type' => $rate['type']
            ]);
        }
    }

    private function getZoneForRate(array $rate): ?\WC_Shipping_Zone {
        $pattern = $this->zone_patterns[$rate['zone_type']] ?? null;
        if (!$pattern) return null;

        return $this->findOrCreateZone($pattern);
    }

    private function updateZoneMethod(\WC_Shipping_Zone $zone, array $rate_data): void {
        $method_id = $zone->add_shipping_method('printify');
        $method = \WC_Shipping_Zones::get_shipping_method($method_id);
        
        if ($method) {
            foreach ($rate_data as $key => $value) {
                $method->update_option($key, $value);
            }
        }
    }
}
