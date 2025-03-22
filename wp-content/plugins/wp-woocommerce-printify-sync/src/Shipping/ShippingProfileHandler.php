<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPIClient;
use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;

class ShippingProfileHandler
{
    private $api_client;
    private $logger;

    public function __construct(PrintifyAPIClient $api_client, Logger $logger)
    {
        $this->api_client = $api_client;
        $this->logger = $logger;
    }

    /**
     * Sync shipping profiles from Printify.
     *
     * @return array Result of sync operation.
     */
    public function syncShippingProfiles()
    {
        $this->logger->info('Starting shipping profiles sync');
        
        $response = $this->api_client->getShippingProfiles();
        
        if (is_wp_error($response)) {
            $this->logger->error('Failed to fetch shipping profiles: ' . $response->get_error_message());
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }

        update_option('wpwps_shipping_profiles', $response);
        
        $this->logger->info('Successfully synced ' . count($response) . ' shipping profiles');
        
        return [
            'success' => true,
            'profiles' => $response,
            'count' => count($response)
        ];
    }

    /**
     * Get available shipping methods for a location.
     *
     * @param array $address Address data.
     * @return array Available shipping methods.
     */
    public function getAvailableShippingMethods($address)
    {
        $profiles = get_option('wpwps_shipping_profiles', []);
        $available_methods = [];

        foreach ($profiles as $profile) {
            if ($this->isAddressInRegion($address, $profile['regions'])) {
                $available_methods[] = [
                    'id' => $profile['id'],
                    'name' => $profile['name'],
                    'rates' => $profile['rates']
                ];
            }
        }

        return $available_methods;
    }

    /**
     * Check if address is within shipping region.
     *
     * @param array $address Address data.
     * @param array $regions Shipping regions.
     * @return bool Whether address is in region.
     */
    private function isAddressInRegion($address, $regions)
    {
        foreach ($regions as $region) {
            if ($region['country'] === $address['country']) {
                if (empty($region['states']) || in_array($address['state'], $region['states'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get shipping options for a provider and line items.
     *
     * @param int   $provider_id Provider ID.
     * @param array $line_items  Array of line items.
     * @param array $address     Shipping address.
     * @return array Shipping options grouped by tier.
     */
    public function getProviderShippingOptions($provider_id, $line_items, $address)
    {
        $response = $this->api_client->getProviderShippingOptions($provider_id, $line_items, $address);
        
        if (is_wp_error($response)) {
            $this->logger->error("Failed to get shipping options for provider {$provider_id}: " . $response->get_error_message());
            return [];
        }

        return $this->formatShippingOptions($response);
    }

    /**
     * Format shipping options into structured tiers.
     *
     * @param array $options Raw shipping options from API.
     * @return array Formatted shipping options.
     */
    private function formatShippingOptions($options)
    {
        $formatted = [
            'express' => [],
            'standard' => [],
            'economy' => []
        ];

        foreach ($options as $option) {
            $tier = $this->determineShippingTier($option);
            
            $formatted[$tier][] = [
                'id' => $option['id'],
                'name' => $option['name'],
                'price' => $option['price'],
                'currency' => $option['currency'],
                'min_delivery_days' => $option['min_delivery_days'],
                'max_delivery_days' => $option['max_delivery_days'],
                'provider_name' => $option['provider_name'] ?? '',
                'method_name' => $option['method_name'] ?? '',
                'tracking_supported' => $option['tracking_supported'] ?? false,
                'weight_limits' => [
                    'min' => $option['min_weight'] ?? 0,
                    'max' => $option['max_weight'] ?? null
                ],
                'quantity_limits' => [
                    'min' => $option['min_quantity'] ?? 1,
                    'max' => $option['max_quantity'] ?? null
                ]
            ];
        }

        return $formatted;
    }

    /**
     * Determine shipping tier based on delivery time and price.
     *
     * @param array $option Shipping option.
     * @return string Shipping tier (express|standard|economy).
     */
    private function determineShippingTier($option)
    {
        // Express shipping usually delivers within 1-3 days
        if ($option['max_delivery_days'] <= 3) {
            return 'express';
        }
        
        // Economy shipping usually takes more than 7 days
        if ($option['min_delivery_days'] > 7) {
            return 'economy';
        }
        
        // Everything else is standard shipping
        return 'standard';
    }

    /**
     * Calculate total shipping cost for line items from multiple providers.
     *
     * @param array $items    Array of line items grouped by provider.
     * @param array $address  Shipping address.
     * @return array Total shipping cost and breakdown.
     */
    public function calculateMultiProviderShipping($items, $address)
    {
        $shipping_costs = [];
        $total_cost = 0;

        foreach ($items as $provider_id => $line_items) {
            $options = $this->getProviderShippingOptions($provider_id, $line_items, $address);
            
            // Default to standard shipping if available, otherwise cheapest option
            $provider_cost = $this->selectDefaultShippingOption($options);
            
            $shipping_costs[$provider_id] = $provider_cost;
            $total_cost += $provider_cost['price'];
        }

        return [
            'total' => $total_cost,
            'breakdown' => $shipping_costs
        ];
    }

    /**
     * Select default shipping option from available tiers.
     *
     * @param array $options Shipping options grouped by tier.
     * @return array Selected shipping option.
     */
    private function selectDefaultShippingOption($options)
    {
        // Prefer standard shipping
        if (!empty($options['standard'])) {
            return $this->getCheapestOption($options['standard']);
        }
        
        // Fall back to economy if no standard options
        if (!empty($options['economy'])) {
            return $this->getCheapestOption($options['economy']);
        }
        
        // Use express if it's the only option
        if (!empty($options['express'])) {
            return $this->getCheapestOption($options['express']);
        }

        return [
            'price' => 0,
            'name' => 'No shipping available',
            'min_delivery_days' => 0,
            'max_delivery_days' => 0
        ];
    }

    /**
     * Get cheapest option from array of shipping options.
     *
     * @param array $options Array of shipping options.
     * @return array Cheapest shipping option.
     */
    private function getCheapestOption($options)
    {
        return array_reduce($options, function($cheapest, $option) {
            if (!$cheapest || $option['price'] < $cheapest['price']) {
                return $option;
            }
            return $cheapest;
        });
    }
}
