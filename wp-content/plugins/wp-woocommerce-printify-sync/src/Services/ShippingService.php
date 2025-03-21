<?php
/**
 * Shipping Service for handling shipping methods.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPIClient;
use ApolloWeb\WPWooCommercePrintifySync\Shipping\PrintifyShippingMethod;

/**
 * Shipping Service for handling shipping methods.
 */
class ShippingService
{
    /**
     * Printify API client.
     *
     * @var PrintifyAPIClient
     */
    private $api_client;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param PrintifyAPIClient $api_client Printify API client.
     * @param Logger           $logger     Logger instance.
     */
    public function __construct(PrintifyAPIClient $api_client, Logger $logger)
    {
        $this->api_client = $api_client;
        $this->logger = $logger;
    }

    /**
     * Initialize shipping methods.
     *
     * @return void
     */
    public function initShippingMethods()
    {
        // Include the shipping method class
        if (!class_exists('ApolloWeb\WPWooCommercePrintifySync\Shipping\PrintifyShippingMethod')) {
            require_once WPWPS_PLUGIN_DIR . 'src/Shipping/PrintifyShippingMethod.php';
        }
    }

    /**
     * Add shipping methods to WooCommerce.
     *
     * @param array $methods WooCommerce shipping methods.
     * @return array Modified shipping methods.
     */
    public function addShippingMethods($methods)
    {
        $methods['printify_shipping'] = 'ApolloWeb\WPWooCommercePrintifySync\Shipping\PrintifyShippingMethod';
        return $methods;
    }

    /**
     * Get shipping rates from Printify.
     *
     * @param array $package Shipping package data.
     * @return array|WP_Error Shipping rates or error.
     */
    public function getShippingRates($package)
    {
        $this->logger->info('Getting shipping rates from Printify');

        // Check if shop ID is set
        $shop_id = $this->api_client->getShopId();
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set, cannot get shipping rates');
            return new \WP_Error('missing_shop_id', 'Shop ID is not set. Please configure it in the settings.');
        }

        // Check if there are Printify products in the cart
        $printify_items = $this->getCartPrintifyItems($package);
        if (empty($printify_items)) {
            $this->logger->info('No Printify products in the cart, using default shipping rates');
            return $this->getDefaultShippingRates();
        }

        // Format the request for shipping rates
        $shipping_request = $this->formatShippingRequest($package, $printify_items);
        
        // Make the request to Printify
        $response = $this->api_client->getShippingRates($shipping_request);
        
        if (is_wp_error($response)) {
            $this->logger->error('Error getting shipping rates: ' . $response->get_error_message());
            return $response;
        }

        // Format the response for WooCommerce
        return $this->formatShippingRates($response);
    }

    /**
     * Get Printify items from the cart.
     *
     * @param array $package Shipping package data.
     * @return array Printify items.
     */
    private function getCartPrintifyItems($package)
    {
        $printify_items = [];
        
        if (empty($package['contents'])) {
            return $printify_items;
        }
        
        foreach ($package['contents'] as $item) {
            $product = $item['data'];
            $product_id = $product->get_id();
            
            // Check if this is a variation
            if ($product->is_type('variation')) {
                $parent_id = $product->get_parent_id();
                $variation_id = $product_id;
                $product_id = $parent_id;
            } else {
                $variation_id = 0;
            }
            
            // Check if this is a Printify product
            $printify_product_id = get_post_meta($product_id, '_printify_product_id', true);
            
            if (!$printify_product_id) {
                continue;
            }
            
            // If this is a variation, get the Printify variant ID
            $printify_variant_id = '';
            if ($variation_id) {
                $printify_variant_id = get_post_meta($variation_id, '_printify_variant_id', true);
                
                if (!$printify_variant_id) {
                    // Try to get from variant mapping
                    $variant_mapping = get_post_meta($product_id, '_printify_variant_ids', true) ?: [];
                    $variant_mapping = maybe_unserialize($variant_mapping);
                    
                    // Loop through mapping to find the variant ID that matches this WC variation ID
                    foreach ($variant_mapping as $p_variant_id => $wc_variation_id) {
                        if ((int) $wc_variation_id === $variation_id) {
                            $printify_variant_id = $p_variant_id;
                            break;
                        }
                    }
                }
            }
            
            // Add to Printify items
            $printify_items[] = [
                'product_id' => $printify_product_id,
                'variant_id' => $printify_variant_id,
                'quantity' => $item['quantity'],
            ];
        }
        
        return $printify_items;
    }

    /**
     * Format shipping request for Printify API.
     *
     * @param array $package      Shipping package data.
     * @param array $printify_items Printify items.
     * @return array Formatted shipping request.
     */
    private function formatShippingRequest($package, $printify_items)
    {
        // Set up address data
        $address = [
            'first_name' => $package['destination']['first_name'] ?? '',
            'last_name' => $package['destination']['last_name'] ?? '',
            'address1' => $package['destination']['address'] ?? '',
            'address2' => $package['destination']['address_2'] ?? '',
            'city' => $package['destination']['city'] ?? '',
            'state' => $package['destination']['state'] ?? '',
            'zip' => $package['destination']['postcode'] ?? '',
            'country' => $package['destination']['country'] ?? 'US',
            'phone' => $package['destination']['phone'] ?? '',
            'email' => $package['destination']['email'] ?? '',
        ];
        
        // Return formatted request
        return [
            'line_items' => $printify_items,
            'address_to' => $address,
        ];
    }

    /**
     * Format shipping rates from Printify for WooCommerce.
     *
     * @param array $response Printify API response.
     * @return array Formatted shipping rates.
     */
    private function formatShippingRates($response)
    {
        $shipping_rates = [];
        
        if (empty($response['shipping_options'])) {
            return $shipping_rates;
        }
        
        foreach ($response['shipping_options'] as $option) {
            $rate_id = sanitize_title($option['name']);
            
            $shipping_rates[$rate_id] = [
                'id' => 'printify_' . $rate_id,
                'label' => $option['name'],
                'cost' => $option['cost'],
                'calc_tax' => 'per_order',
                'meta_data' => [
                    'printify_shipping_id' => $option['id'] ?? '',
                    'printify_carrier' => $option['carrier'] ?? '',
                    'printify_service' => $option['service'] ?? '',
                    'printify_estimated_delivery' => $option['estimated_delivery'] ?? '',
                ],
            ];
        }
        
        return $shipping_rates;
    }

    /**
     * Get default shipping rates when Printify API is not available.
     *
     * @return array Default shipping rates.
     */
    private function getDefaultShippingRates()
    {
        // These defaults should be configurable via settings
        return [
            'standard' => [
                'id' => 'printify_standard',
                'label' => __('Standard Shipping', 'wp-woocommerce-printify-sync'),
                'cost' => 5.99,
                'calc_tax' => 'per_order',
            ],
            'express' => [
                'id' => 'printify_express',
                'label' => __('Express Shipping', 'wp-woocommerce-printify-sync'),
                'cost' => 12.99,
                'calc_tax' => 'per_order',
            ],
        ];
    }
}
