<?php
/**
 * Printify Shipping Method.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Shipping
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

/**
 * Printify Shipping Method for WooCommerce.
 */
class PrintifyShippingMethod extends \WC_Shipping_Method {
    /**
     * ShippingProfileManager instance.
     *
     * @var ShippingProfileManager
     */
    private $profile_manager;
    
    /**
     * Cache of shipping rates to avoid duplicate API calls.
     *
     * @var array
     */
    private static $rate_cache = [];

    /**
     * Constructor.
     *
     * @param int $instance_id Shipping method instance ID.
     */
    public function __construct($instance_id = 0) {
        $this->id = 'printify_shipping';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Printify Shipping', 'wp-woocommerce-printify-sync');
        $this->method_description = __('Dynamic shipping rates from Printify print providers.', 'wp-woocommerce-printify-sync');
        $this->supports = [
            'shipping-zones',
            'instance-settings',
        ];

        $this->init();
        
        // Get profile manager from global variable
        global $wpwps_shipping_profile_manager;
        $this->profile_manager = $wpwps_shipping_profile_manager;
    }

    /**
     * Initialize settings.
     *
     * @return void
     */
    public function init() {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Method Title', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wp-woocommerce-printify-sync'),
                'default' => __('Printify Shipping', 'wp-woocommerce-printify-sync'),
                'desc_tip' => true,
            ],
            'fallback_cost' => [
                'title' => __('Fallback Cost', 'wp-woocommerce-printify-sync'),
                'type' => 'price',
                'description' => __('If no shipping rate is found, this cost will be used.', 'wp-woocommerce-printify-sync'),
                'default' => '10.00',
                'desc_tip' => true,
            ],
            'show_delivery_time' => [
                'title' => __('Show Delivery Time', 'wp-woocommerce-printify-sync'),
                'type' => 'checkbox',
                'description' => __('Show estimated delivery time in shipping method name.', 'wp-woocommerce-printify-sync'),
                'default' => 'yes',
                'desc_tip' => true,
            ],
            'show_provider_name' => [
                'title' => __('Show Provider Name', 'wp-woocommerce-printify-sync'),
                'type' => 'checkbox',
                'description' => __('Show print provider name in shipping method name.', 'wp-woocommerce-printify-sync'),
                'default' => 'yes',
                'desc_tip' => true,
            ],
            'multi_tiered_pricing' => [
                'title' => __('Multi-tiered Pricing', 'wp-woocommerce-printify-sync'),
                'type' => 'checkbox',
                'description' => __('Use first_item + additional_item pricing structure. If disabled, all items will be charged at first_item rate.', 'wp-woocommerce-printify-sync'),
                'default' => 'yes',
                'desc_tip' => true,
            ],
            'separate_providers' => [
                'title' => __('Separate Provider Shipping', 'wp-woocommerce-printify-sync'),
                'type' => 'checkbox',
                'description' => __('Show separate shipping options for each print provider.', 'wp-woocommerce-printify-sync'),
                'default' => 'yes',
                'desc_tip' => true,
            ],
        ];

        $this->title = $this->get_option('title');
    }

    /**
     * Calculate shipping.
     *
     * @param array $package Shipping package.
     * @return void
     */
    public function calculate_shipping($package = []) {
        if (!$this->profile_manager) {
            return;
        }

        // Group items by provider
        $provider_items = [];
        $total_provider_count = 0;
        
        foreach ($package['contents'] as $item) {
            $product = $item['data'];
            $provider_id = $product->get_meta('_printify_provider_id');
            
            if (!$provider_id) {
                continue;
            }
            
            if (!isset($provider_items[$provider_id])) {
                $provider_items[$provider_id] = [];
                $total_provider_count++;
            }
            
            $provider_items[$provider_id][] = [
                'product_id' => $product->get_id(),
                'quantity' => $item['quantity'],
                'variation_id' => $product->is_type('variation') ? $product->get_id() : 0,
                'parent_id' => $product->is_type('variation') ? $product->get_parent_id() : $product->get_id(),
            ];
        }
        
        // If no printify products, don't calculate
        if (empty($provider_items)) {
            return;
        }

        $destination = [
            'country' => $package['destination']['country'],
            'state' => $package['destination']['state'],
            'postcode' => $package['destination']['postcode'],
        ];

        // Get settings
        $show_delivery_time = $this->get_option('show_delivery_time') === 'yes';
        $show_provider_name = $this->get_option('show_provider_name') === 'yes';
        $multi_tiered_pricing = $this->get_option('multi_tiered_pricing') === 'yes';
        $separate_providers = $this->get_option('separate_providers') === 'yes' && $total_provider_count > 1;
        $fallback_cost = $this->get_option('fallback_cost');
        
        // Create cache key for current request
        $cache_key = md5(json_encode([
            'destination' => $destination,
            'providers' => array_keys($provider_items),
        ]));
        
        // Check if we have cached rates
        if (isset(self::$rate_cache[$cache_key])) {
            $shipping_rates = self::$rate_cache[$cache_key];
        } else {
            // Calculate shipping for each provider
            $shipping_rates = [];
            
            foreach ($provider_items as $provider_id => $items) {
                // Create a cache sub-key for this specific provider
                $provider_cache_key = $provider_id . '_' . md5(json_encode($destination));
                
                // Check if we have this provider in the cache
                if (isset(self::$rate_cache[$provider_cache_key])) {
                    $shipping_costs = self::$rate_cache[$provider_cache_key];
                } else {
                    // Calculate shipping cost for this provider
                    $shipping_costs = $this->profile_manager->calculateShippingCost($provider_id, $items, $destination);
                    
                    // Cache the result
                    if (!is_wp_error($shipping_costs)) {
                        self::$rate_cache[$provider_cache_key] = $shipping_costs;
                    }
                }
                
                if ($shipping_costs && !empty($shipping_costs['methods'])) {
                    $shipping_rates[$provider_id] = $shipping_costs;
                }
            }
            
            // Cache the combined results
            self::$rate_cache[$cache_key] = $shipping_rates;
        }
        
        // If no shipping rates found for any provider, use fallback
        if (empty($shipping_rates)) {
            $this->add_fallback_rate($fallback_cost, $show_provider_name);
            return;
        }
        
        // Add rates
        if ($separate_providers) {
            // Add separate rates for each provider
            foreach ($shipping_rates as $provider_id => $shipping_costs) {
                $this->add_provider_shipping_rates(
                    $provider_id,
                    $shipping_costs,
                    $provider_items[$provider_id],
                    $show_delivery_time,
                    $show_provider_name,
                    $multi_tiered_pricing
                );
            }
        } else {
            // Combine rates for all providers
            $this->add_combined_shipping_rates(
                $shipping_rates,
                $provider_items,
                $show_delivery_time,
                $show_provider_name,
                $multi_tiered_pricing
            );
        }
    }
    
    /**
     * Add a fallback shipping rate.
     *
     * @param float $cost           Fallback cost.
     * @param bool  $show_provider  Whether to show provider name.
     * @return void
     */
    private function add_fallback_rate($cost, $show_provider) {
        $this->add_rate([
            'id' => $this->get_rate_id("fallback"),
            'label' => $this->title . ($show_provider ? ' (' . __('Unknown Provider', 'wp-woocommerce-printify-sync') . ')' : ''),
            'cost' => $cost,
            'meta_data' => [
                'is_fallback' => true,
            ],
        ]);
    }
    
    /**
     * Add shipping rates for a specific provider.
     *
     * @param int   $provider_id        Provider ID.
     * @param array $shipping_costs     Shipping costs data.
     * @param array $items              Items for this provider.
     * @param bool  $show_delivery_time Whether to show delivery time.
     * @param bool  $show_provider_name Whether to show provider name.
     * @param bool  $multi_tiered       Whether to use multi-tiered pricing.
     * @return void
     */
    private function add_provider_shipping_rates($provider_id, $shipping_costs, $items, $show_delivery_time, $show_provider_name, $multi_tiered) {
        $total_quantity = 0;
        foreach ($items as $item) {
            $total_quantity += $item['quantity'];
        }
        
        // Format provider name
        $provider_name = '';
        if ($show_provider_name && isset($shipping_costs['provider_name'])) {
            $provider_name = $shipping_costs['provider_name'];
        }
        
        foreach ($shipping_costs['methods'] as $method) {
            $method_name = $method['name'];
            
            // Add delivery time if available and enabled
            if ($show_delivery_time && isset($method['min_delivery_days']) && isset($method['max_delivery_days'])) {
                $method_name .= sprintf(
                    ' (%d-%d %s)',
                    $method['min_delivery_days'],
                    $method['max_delivery_days'],
                    __('days', 'wp-woocommerce-printify-sync')
                );
            }
            
            // Add provider name if enabled
            if (!empty($provider_name)) {
                $method_name .= sprintf(' (%s)', $provider_name);
            }
            
            // Calculate cost based on tiered pricing
            $cost = $method['cost'];
            if ($multi_tiered && $total_quantity > 1 && isset($method['first_item']) && isset($method['additional_items'])) {
                $cost = $method['first_item'] + ($method['additional_items'] * ($total_quantity - 1));
            }
            
            $this->add_rate([
                'id' => $this->get_rate_id("provider_{$provider_id}_method_{$method['id']}"),
                'label' => $method_name,
                'cost' => $cost,
                'meta_data' => [
                    'provider_id' => $provider_id,
                    'method_id' => $method['id'],
                    'carrier' => isset($method['carrier']) ? $method['carrier'] : '',
                    'delivery_days' => [
                        'min' => isset($method['min_delivery_days']) ? $method['min_delivery_days'] : null,
                        'max' => isset($method['max_delivery_days']) ? $method['max_delivery_days'] : null,
                    ],
                ],
            ]);
        }
    }
    
    /**
     * Add combined shipping rates for all providers.
     *
     * @param array $shipping_rates     All shipping rates by provider.
     * @param array $provider_items     Items grouped by provider.
     * @param bool  $show_delivery_time Whether to show delivery time.
     * @param bool  $show_provider_name Whether to show provider name.
     * @param bool  $multi_tiered       Whether to use multi-tiered pricing.
     * @return void
     */
    private function add_combined_shipping_rates($shipping_rates, $provider_items, $show_delivery_time, $show_provider_name, $multi_tiered) {
        // Get common shipping methods across providers
        $common_methods = [];
        $provider_quantities = [];
        
        // Get total quantities for each provider
        foreach ($provider_items as $provider_id => $items) {
            $total_quantity = 0;
            foreach ($items as $item) {
                $total_quantity += $item['quantity'];
            }
            $provider_quantities[$provider_id] = $total_quantity;
        }
        
        // Extract all available shipping methods
        foreach ($shipping_rates as $provider_id => $shipping_costs) {
            foreach ($shipping_costs['methods'] as $method) {
                $method_key = strtolower(preg_replace('/[^a-z0-9]/', '', $method['name']));
                
                if (!isset($common_methods[$method_key])) {
                    $common_methods[$method_key] = [
                        'name' => $method['name'],
                        'providers' => [],
                        'delivery_days' => [
                            'min' => isset($method['min_delivery_days']) ? $method['min_delivery_days'] : null,
                            'max' => isset($method['max_delivery_days']) ? $method['max_delivery_days'] : null,
                        ],
                    ];
                }
                
                // Calculate cost based on tiered pricing
                $cost = $method['cost'];
                if ($multi_tiered && $provider_quantities[$provider_id] > 1 && isset($method['first_item']) && isset($method['additional_items'])) {
                    $cost = $method['first_item'] + ($method['additional_items'] * ($provider_quantities[$provider_id] - 1));
                }
                
                $common_methods[$method_key]['providers'][$provider_id] = [
                    'cost' => $cost,
                    'carrier' => isset($method['carrier']) ? $method['carrier'] : '',
                    'method_id' => $method['id'],
                ];
                
                // Update delivery days to be the max across all providers
                if (isset($method['max_delivery_days']) && 
                   ($common_methods[$method_key]['delivery_days']['max'] === null || 
                    $method['max_delivery_days'] > $common_methods[$method_key]['delivery_days']['max'])) {
                    $common_methods[$method_key]['delivery_days']['max'] = $method['max_delivery_days'];
                }
                
                if (isset($method['min_delivery_days']) && 
                   ($common_methods[$method_key]['delivery_days']['min'] === null || 
                    $method['min_delivery_days'] < $common_methods[$method_key]['delivery_days']['min'])) {
                    $common_methods[$method_key]['delivery_days']['min'] = $method['min_delivery_days'];
                }
            }
        }
        
        // Add rates for each common method
        foreach ($common_methods as $method_key => $method_data) {
            $providers_count = count($method_data['providers']);
            
            // Only add methods available for all providers
            if ($providers_count === count($shipping_rates)) {
                $method_name = $method_data['name'];
                
                // Add delivery time if available and enabled
                if ($show_delivery_time && 
                    $method_data['delivery_days']['min'] !== null && 
                    $method_data['delivery_days']['max'] !== null) {
                    $method_name .= sprintf(
                        ' (%d-%d %s)',
                        $method_data['delivery_days']['min'],
                        $method_data['delivery_days']['max'],
                        __('days', 'wp-woocommerce-printify-sync')
                    );
                }
                
                // Add provider names if enabled
                if ($show_provider_name) {
                    $provider_names = [];
                    foreach ($method_data['providers'] as $provider_id => $provider_data) {
                        if (isset($shipping_rates[$provider_id]['provider_name'])) {
                            $provider_names[] = $shipping_rates[$provider_id]['provider_name'];
                        }
                    }
                    
                    if (!empty($provider_names)) {
                        $method_name .= ' (' . implode(', ', $provider_names) . ')';
                    }
                }
                
                // Calculate total cost
                $total_cost = 0;
                foreach ($method_data['providers'] as $provider_id => $provider_data) {
                    $total_cost += $provider_data['cost'];
                }
                
                $this->add_rate([
                    'id' => $this->get_rate_id("combined_method_{$method_key}"),
                    'label' => $method_name,
                    'cost' => $total_cost,
                    'meta_data' => [
                        'providers' => $method_data['providers'],
                        'delivery_days' => $method_data['delivery_days'],
                        'is_combined' => true,
                    ],
                ]);
            }
        }
    }
}
