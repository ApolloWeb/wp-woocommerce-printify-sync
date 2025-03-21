<?php
/**
 * Cart Handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Shipping
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Handles custom cart calculation for multi-provider orders.
 */
class CartHandler {
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Initialize cart handler.
     *
     * @return void
     */
    public function init() {
        // Handle modifying cart items
        add_filter('woocommerce_cart_shipping_packages', [$this, 'modifyShippingPackages']);
        
        // Handle displaying shipping methods on checkout
        add_filter('woocommerce_package_rates', [$this, 'filterShippingRates'], 10, 2);
        
        // Save selected shipping method with provider info for order
        add_action('woocommerce_checkout_create_order_shipping_item', [$this, 'saveShippingMethodMeta'], 10, 4);
        
        // Display provider info on order details
        add_filter('woocommerce_get_order_item_totals', [$this, 'addProviderInfoToOrderTotals'], 10, 3);
    }

    /**
     * Modify shipping packages to group by provider.
     *
     * @param array $packages Shipping packages.
     * @return array
     */
    public function modifyShippingPackages($packages) {
        // If there's only one package, check if we need to split by provider
        if (count($packages) === 1 && !empty($packages[0]['contents'])) {
            $provider_packages = [];
            $non_printify_items = [];
            
            foreach ($packages[0]['contents'] as $item_key => $item) {
                $product = $item['data'];
                $provider_id = $product->get_meta('_printify_provider_id');
                
                if ($provider_id) {
                    if (!isset($provider_packages[$provider_id])) {
                        // Clone the original package
                        $provider_packages[$provider_id] = $packages[0];
                        $provider_packages[$provider_id]['contents'] = [];
                        $provider_packages[$provider_id]['contents_cost'] = 0;
                        $provider_packages[$provider_id]['printify_provider_id'] = $provider_id;
                    }
                    
                    $provider_packages[$provider_id]['contents'][$item_key] = $item;
                    $provider_packages[$provider_id]['contents_cost'] += $item['line_total'];
                } else {
                    $non_printify_items[$item_key] = $item;
                }
            }
            
            // If we have multiple providers, return split packages
            if (count($provider_packages) > 1) {
                $result = array_values($provider_packages);
                
                // If there are non-printify items, add them as a separate package
                if (!empty($non_printify_items)) {
                    $non_printify_package = $packages[0];
                    $non_printify_package['contents'] = $non_printify_items;
                    $non_printify_package['contents_cost'] = array_sum(array_column($non_printify_items, 'line_total'));
                    $result[] = $non_printify_package;
                }
                
                return $result;
            }
        }
        
        // No need to split packages
        return $packages;
    }

    /**
     * Filter shipping rates to ensure each provider has the correct methods.
     *
     * @param array $rates    Available shipping rates.
     * @param array $package  Shipping package.
     * @return array
     */
    public function filterShippingRates($rates, $package) {
        // If this package has a specific provider, only show matching provider methods
        if (isset($package['printify_provider_id'])) {
            $provider_id = $package['printify_provider_id'];
            
            foreach ($rates as $rate_id => $rate) {
                $method_meta = $rate->get_meta_data();
                
                // Remove rates that don't match this provider
                if (isset($method_meta['provider_id']) && $method_meta['provider_id'] != $provider_id) {
                    unset($rates[$rate_id]);
                }
            }
        }
        
        return $rates;
    }

    /**
     * Save shipping method meta data to order.
     *
     * @param WC_Order_Item_Shipping $item          Shipping item.
     * @param string                 $package_key    Package key.
     * @param array                  $package        Shipping package.
     * @param WC_Order               $order          Order object.
     * @return void
     */
    public function saveShippingMethodMeta($item, $package_key, $package, $order) {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        if (isset($chosen_methods[$package_key])) {
            $rate_id = $chosen_methods[$package_key];
            $shipping_rates = $package['rates'];
            
            if (isset($shipping_rates[$rate_id])) {
                $rate = $shipping_rates[$rate_id];
                $meta_data = $rate->get_meta_data();
                
                // Save provider and method info
                if (isset($meta_data['provider_id'])) {
                    $item->add_meta_data('_printify_provider_id', $meta_data['provider_id']);
                    
                    if (isset($meta_data['method_id'])) {
                        $item->add_meta_data('_printify_method_id', $meta_data['method_id']);
                    }
                    
                    if (isset($meta_data['carrier'])) {
                        $item->add_meta_data('_printify_carrier', $meta_data['carrier']);
                    }
                    
                    if (isset($meta_data['delivery_days'])) {
                        $item->add_meta_data('_printify_delivery_days', $meta_data['delivery_days']);
                    }
                    
                    if (isset($package['printify_provider_id'])) {
                        $item->add_meta_data('_printify_package_provider_id', $package['printify_provider_id']);
                    }
                }
            }
        }
    }

    /**
     * Add provider info to order totals.
     *
     * @param array     $total_rows Total rows.
     * @param WC_Order  $order      Order object.
     * @param string    $tax_display Tax display mode.
     * @return array
     */
    public function addProviderInfoToOrderTotals($total_rows, $order, $tax_display) {
        $shipping_methods = $order->get_shipping_methods();
        
        // Skip modifying if no shipping methods or only one
        if (empty($shipping_methods) || count($shipping_methods) <= 1) {
            return $total_rows;
        }
        
        // Check if we have any Printify shipping methods
        $has_printify_method = false;
        foreach ($shipping_methods as $method) {
            if ($method->get_meta('_printify_provider_id')) {
                $has_printify_method = true;
                break;
            }
        }
        
        if (!$has_printify_method) {
            return $total_rows;
        }
        
        // Remove default shipping row
        if (isset($total_rows['shipping'])) {
            unset($total_rows['shipping']);
        }
        
        // Add a row for each shipping method with provider info
        $i = 0;
        foreach ($shipping_methods as $method) {
            $method_name = $method->get_name();
            $provider_id = $method->get_meta('_printify_provider_id');
            
            if ($provider_id) {
                // Get provider name
                global $wpwps_shipping_profile_manager;
                if ($wpwps_shipping_profile_manager) {
                    $provider_info = $wpwps_shipping_profile_manager->getShippingProfiles($provider_id);
                    if (!empty($provider_info) && isset($provider_info['name'])) {
                        $method_name .= ' (' . $provider_info['name'] . ')';
                    }
                }
            }
            
            $total_rows['shipping_' . $i] = [
                'label' => $i === 0 ? __('Shipping:', 'woocommerce') : __('Additional Shipping:', 'wp-woocommerce-printify-sync'),
                'value' => $method_name . ': ' . wc_price($method->get_total(), ['currency' => $order->get_currency()]),
            ];
            
            $i++;
        }
        
        return $total_rows;
    }
}
