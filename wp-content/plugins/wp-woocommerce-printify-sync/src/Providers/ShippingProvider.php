<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ShippingProvider extends ServiceProvider
{
    private const OPTION_PREFIX = 'wpwps_';
    private Client $client;

    public function boot(): void
    {
        $this->client = new Client([
            'base_uri' => 'https://api.printify.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getApiKey(),
                'Accept' => 'application/json',
            ]
        ]);

        $this->registerAdminMenu(
            'WC Printify Shipping',
            'Shipping',
            'manage_woocommerce',
            'wpwps-shipping',
            [$this, 'renderShippingPage']
        );

        $this->registerAjaxEndpoint('wpwps_sync_shipping', [$this, 'syncShippingProfiles']);
        $this->registerAjaxEndpoint('wpwps_update_shipping_mapping', [$this, 'updateShippingMapping']);

        add_filter('woocommerce_shipping_methods', [$this, 'addPrintifyShippingMethod']);
    }

    public function renderShippingPage(): void
    {
        $data = [
            'providers' => $this->getProviders(),
            'shipping_zones' => $this->getShippingZones(),
            'shipping_mappings' => get_option(self::OPTION_PREFIX . 'shipping_mappings', []),
            'shipping_profiles' => $this->getShippingProfiles()
        ];

        echo $this->view->render('wpwps-shipping', $data);
    }

    public function syncShippingProfiles(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        try {
            $shopId = get_option(self::OPTION_PREFIX . 'shop_id');
            $profiles = [];

            // Get shipping profiles for each provider
            foreach ($this->getProviders() as $provider) {
                $response = $this->client->get("shops/{$shopId}/shipping/{$provider['id']}.json");
                $providerProfiles = json_decode($response->getBody()->getContents(), true);
                $profiles[$provider['id']] = $providerProfiles;
            }

            update_option(self::OPTION_PREFIX . 'shipping_profiles', $profiles);
            $this->createShippingZones($profiles);

            wp_send_json_success([
                'message' => 'Shipping profiles synchronized successfully',
                'profiles' => $profiles
            ]);
        } catch (GuzzleException $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function updateShippingMapping(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        $mappings = $_POST['mappings'] ?? [];
        update_option(self::OPTION_PREFIX . 'shipping_mappings', $mappings);
        wp_send_json_success(['message' => 'Shipping mappings updated successfully']);
    }

    public function addPrintifyShippingMethod(array $methods): array
    {
        $methods['printify'] = \ApolloWeb\WPWooCommercePrintifySync\Shipping\PrintifyShipping::class;
        return $methods;
    }

    private function getProviders(): array
    {
        try {
            $shopId = get_option(self::OPTION_PREFIX . 'shop_id');
            $response = $this->client->get("shops/{$shopId}/providers.json");
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return [];
        }
    }

    private function getShippingProfiles(): array
    {
        return get_option(self::OPTION_PREFIX . 'shipping_profiles', []);
    }

    private function getShippingZones(): array
    {
        $zones = [];
        foreach (\WC_Shipping_Zones::get_zones() as $zone) {
            $zones[] = [
                'id' => $zone['id'],
                'name' => $zone['zone_name'],
                'regions' => array_map(function($location) {
                    return [
                        'type' => $location->type,
                        'code' => $location->code
                    ];
                }, $zone['zone_locations'])
            ];
        }
        return $zones;
    }

    private function createShippingZones(array $profiles): void
    {
        foreach ($profiles as $providerId => $providerProfiles) {
            foreach ($providerProfiles as $profile) {
                $this->createOrUpdateShippingZone($providerId, $profile);
            }
        }
    }

    private function createOrUpdateShippingZone(string $providerId, array $profile): void
    {
        $zoneName = "Printify - {$providerId} - {$profile['name']}";
        $zone = $this->findExistingZone($zoneName);

        if (!$zone) {
            $zone = new \WC_Shipping_Zone();
            $zone->set_zone_name($zoneName);
        }

        // Clear existing locations
        $zone->clear_zone_locations();

        // Add locations from profile
        foreach ($profile['locations'] as $location) {
            $zone->add_location($location['country'], 'country');
        }

        $zone->save();

        // Add shipping method
        $this->addShippingMethodToZone($zone->get_id(), $profile);
    }

    private function findExistingZone(string $zoneName): ?\WC_Shipping_Zone
    {
        foreach (\WC_Shipping_Zones::get_zones() as $zone) {
            if ($zone['zone_name'] === $zoneName) {
                return new \WC_Shipping_Zone($zone['id']);
            }
        }
        return null;
    }

    private function addShippingMethodToZone(int $zoneId, array $profile): void
    {
        $zone = new \WC_Shipping_Zone($zoneId);
        $methods = $zone->get_shipping_methods();

        // Remove existing Printify methods
        foreach ($methods as $method) {
            if ($method->id === 'printify') {
                $zone->delete_shipping_method($method->instance_id);
            }
        }

        // Add new method
        $instance_id = $zone->add_shipping_method('printify');
        $method = \WC_Shipping_Zones::get_shipping_method($instance_id);

        if ($method) {
            $method->update_option('title', $profile['name']);
            $method->update_option('cost', $profile['rates']['first_item']);
            $method->update_option('additional_cost', $profile['rates']['additional_item']);
        }
    }

    private function getApiKey(): string
    {
        return get_option(self::OPTION_PREFIX . 'api_key', '');
    }
}