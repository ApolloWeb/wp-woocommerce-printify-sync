<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ShippingProfileManager {
    private $api;
    private $cache_key = 'wpps_shipping_profiles';
    private $cache_expiration = 3600; // 1 hour

    public function __construct(PrintifyApi $api) {
        $this->api = $api;
    }

    public function getProviderShippingProfile(int $provider_id): array {
        $cached = get_transient($this->cache_key . '_' . $provider_id);
        if ($cached !== false) {
            return $cached;
        }

        $profile = $this->api->getPrintProviderShipping($provider_id);
        set_transient($this->cache_key . '_' . $provider_id, $profile, $this->cache_expiration);
        
        return $profile;
    }

    public function calculateShippingCost(array $items, array $address): float {
        $shipping_data = [
            'line_items' => $this->prepareLineItems($items),
            'address' => $this->formatAddress($address)
        ];

        $rates = $this->api->calculateShipping($shipping_data);
        return $rates['amount'] ?? 0.0;
    }

    private function prepareLineItems(array $items): array {
        return array_map(function($item) {
            return [
                'product_id' => $item['printify_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity']
            ];
        }, $items);
    }

    private function formatAddress(array $address): array {
        return [
            'first_name' => $address['first_name'] ?? '',
            'last_name' => $address['last_name'] ?? '',
            'address1' => $address['address_1'] ?? '',
            'address2' => $address['address_2'] ?? '',
            'city' => $address['city'] ?? '',
            'state' => $address['state'] ?? '',
            'country' => $address['country'] ?? '',
            'zip' => $address['postcode'] ?? ''
        ];
    }
}
