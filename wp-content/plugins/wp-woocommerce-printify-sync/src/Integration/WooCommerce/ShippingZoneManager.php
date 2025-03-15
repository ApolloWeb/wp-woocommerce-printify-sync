<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Integration\WooCommerce;

use ApolloWeb\WPWooCommercePrintifySync\Foundation\AppContext;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LoggerAwareTrait;
use ApolloWeb\WPWooCommercePrintifySync\Services\ExchangeRateService;

class ShippingZoneManager
{
    use LoggerAwareTrait;

    private AppContext $context;
    private ExchangeRateService $exchangeRateService;
    private string $currentTime = '2025-03-15 20:18:01';
    private string $currentUser = 'ApolloWeb';

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->context = AppContext::getInstance();
        $this->exchangeRateService = $exchangeRateService;
    }

    public function syncShippingZones(array $printifyProfiles): void
    {
        try {
            foreach ($printifyProfiles as $profile) {
                $this->createOrUpdateZone($profile);
            }

            $this->log('info', 'Shipping zones synced successfully', [
                'profiles_count' => count($printifyProfiles)
            ]);

        } catch (\Exception $e) {
            $this->log('error', 'Failed to sync shipping zones', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function createOrUpdateZone(array $profile): void
    {
        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Check if zone exists
            $zoneId = $this->findExistingZone($profile['id']);

            if (!$zoneId) {
                // Create new zone
                $zone = new \WC_Shipping_Zone();
                $zone->set_zone_name("Printify - {$profile['name']}");
                $zone->save();
                $zoneId = $zone->get_id();

                // Add regions to zone
                $this->addRegionsToZone($zone, $profile['regions']);
            }

            // Update shipping method settings
            $this->updateShippingMethods($zoneId, $profile);

            // Save profile data
            $this->saveProfileData($zoneId, $profile);

            $wpdb->query('COMMIT');

            $this->log('info', 'Shipping zone updated', [
                'zone_id' => $zoneId,
                'profile_id' => $profile['id']
            ]);

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    private function findExistingZone(string $profileId): ?int
    {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT wc_zone_id 
            FROM {$wpdb->prefix}wpwps_shipping_profiles 
            WHERE printify_profile_id = %s",
            $profileId
        ));
    }

    private function addRegionsToZone(\WC_Shipping_Zone $zone, array $regions): void
    {
        $existingLocations = $zone->get_zone_locations();
        $existingCodes = array_map(
            fn($location) => $location->code,
            $existingLocations
        );

        foreach ($regions as $region) {
            foreach ($region['countries'] as $countryCode) {
                if (!in_array($countryCode, $existingCodes)) {
                    $zone->add_location($countryCode, 'country');
                }
            }

            // Handle states/regions if provided
            if (!empty($region['states'])) {
                foreach ($region['states'] as $state) {
                    $stateCode = $region['country_code'] . ':' . $state;
                    if (!in_array($stateCode, $existingCodes)) {
                        $zone->add_location($stateCode, 'state');
                    }
                }
            }
        }

        $zone->save();
    }

    private function updateShippingMethods(int $zoneId, array $profile): void
    {
        $zone = new \WC_Shipping_Zone($zoneId);
        $methods = $zone->get_shipping_methods();

        // Find or create Printify shipping method
        $printifyMethod = null;
        foreach ($methods as $method) {
            if ($method->id === 'printify_shipping') {
                $printifyMethod = $method;
                break;
            }
        }

        if (!$printifyMethod) {
            $zone->add_shipping_method('printify_shipping');
            $methods = $zone->get_shipping_methods(true);
            $printifyMethod = end($methods);
        }

        // Update method settings
        $settings = array_merge(
            $printifyMethod->instance_settings,
            [
                'title' => $profile['name'],
                'provider_profiles' => [$profile['id']],
                'cost_rates' => $this->convertCostRates($profile['rates']),
                'last_updated' => $this->currentTime,
                'updated_by' => $this->currentUser
            ]
        );

        foreach ($settings as $key => $value) {
            $printifyMethod->instance_settings[$key] = $value;
        }

        update_option(
            $printifyMethod->get_instance_option_key(),
            $printifyMethod->instance_settings,
            'yes'
        );
    }

    private function convertCostRates(array $rates): array
    {
        $storeCurrency = get_woocommerce_currency();
        
        return array_map(function($rate) use ($storeCurrency) {
            $rate['cost'] = $this->exchangeRateService->convert(
                $rate['cost'],
                'USD',
                $storeCurrency
            );
            return $rate;
        }, $rates);
    }

    private function saveProfileData(int $zoneId, array $profile): void
    {
        global $wpdb;

        $wpdb->replace(
            $wpdb->prefix . 'wpwps_shipping_profiles',
            [
                'printify_profile_id' => $profile['id'],
                'wc_zone_id' => $zoneId,
                'profile_data' => wp_json_encode($profile),
                'is_active' => 1,
                'last_synced' => $this->currentTime,
                'created_at' => $this->currentTime,
                'updated_at' => $this->currentTime
            ]
        );
    }
}