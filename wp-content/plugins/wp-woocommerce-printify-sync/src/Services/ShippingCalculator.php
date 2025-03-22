<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPIClient;

class ShippingCalculator {
    private $api_client;
    private $logger;
    
    public function __construct(PrintifyAPIClient $api_client, Logger $logger) {
        $this->api_client = $api_client;
        $this->logger = $logger;
    }

    /**
     * Calculate shipping for cart items grouped by provider
     */
    public function calculateMultiProviderShipping($cart_items, $shipping_address) {
        $providers = $this->groupCartItemsByProvider($cart_items);
        $shipping_costs = [];
        
        foreach ($providers as $provider_id => $items) {
            $response = $this->api_client->getProviderShippingOptions(
                $provider_id,
                $this->formatLineItems($items),
                $shipping_address
            );
            
            if (!is_wp_error($response)) {
                $shipping_costs[$provider_id] = $this->convertCurrency($response['shipping_costs']);
            }
        }
        
        return $shipping_costs;
    }

    /**
     * Group cart items by print provider
     */
    private function groupCartItemsByProvider($cart_items) {
        $grouped = [];
        
        foreach ($cart_items as $item) {
            $provider_id = $this->getItemProviderId($item);
            if ($provider_id) {
                if (!isset($grouped[$provider_id])) {
                    $grouped[$provider_id] = [];
                }
                $grouped[$provider_id][] = $item;
            }
        }
        
        return $grouped;
    }

    /**
     * Convert USD price to store currency
     */
    private function convertCurrency($amount) {
        if (!function_exists('wc_get_price_decimals')) {
            return $amount;
        }

        // Get CURCY rate if available
        $rate = 1;
        if (function_exists('wmc_get_price')) {
            return wmc_get_price($amount);
        }

        return round($amount * $rate, wc_get_price_decimals());
    }

    /**
     * Get provider ID from cart item
     */
    private function getItemProviderId($item) {
        $product = $item['data'];
        return $product->get_meta('_printify_provider_id');
    }

    /**
     * Format line items for API request
     */
    private function formatLineItems($items) {
        $line_items = [];
        
        foreach ($items as $item) {
            $line_items[] = [
                'product_id' => $item['data']->get_meta('_printify_product_id'),
                'variant_id' => $item['data']->get_meta('_printify_variant_id'),
                'quantity' => $item['quantity']
            ];
        }
        
        return $line_items;
    }

    /**
     * Parse complete order pricing breakdown
     */
    public function parseOrderPricing($printify_order) {
        return [
            'customer' => [
                'subtotal' => $printify_order['total_price'] - $printify_order['total_shipping'],
                'shipping' => $printify_order['total_shipping'],
                'total' => $printify_order['total_price']
            ],
            'costs' => [
                'production' => $printify_order['production_costs'],
                'shipping' => $printify_order['shipping_costs'],
                'total' => $printify_order['production_costs'] + $printify_order['shipping_costs']
            ],
            'profit' => [
                'items' => $printify_order['total_price'] - $printify_order['total_shipping'] - $printify_order['production_costs'],
                'shipping' => $printify_order['total_shipping'] - $printify_order['shipping_costs'],
                'total' => $printify_order['total_price'] - $printify_order['production_costs'] - $printify_order['shipping_costs']
            ],
            'line_items' => array_map(function($item) {
                return [
                    'retail_price' => $item['retail_price'],
                    'cost' => $item['cost'],
                    'shipping_cost' => $item['shipping_cost'] ?? 0,
                    'profit' => $item['retail_price'] - $item['cost'] - ($item['shipping_cost'] ?? 0)
                ];
            }, $printify_order['line_items'])
        ];
    }

    /**
     * Calculate shipping margin
     */
    public function calculateShippingMargin($retail_shipping, $cost_shipping) {
        if ($retail_shipping <= 0) {
            return 0;
        }
        return ($retail_shipping - $cost_shipping) / $retail_shipping * 100;
    }
}
