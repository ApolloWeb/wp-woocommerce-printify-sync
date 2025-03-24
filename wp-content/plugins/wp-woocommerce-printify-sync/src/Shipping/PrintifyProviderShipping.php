<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

use WC_Shipping_Method;

class PrintifyProviderShipping extends WC_Shipping_Method {
    /**
     * Constructor
     *
     * @param int $instance_id Instance ID
     */
    public function __construct($instance_id = 0) 
    {
        $this->id = 'printify_provider';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Printify Provider Shipping', 'wp-woocommerce-printify-sync');
        $this->method_description = __('Shipping rates from Printify print providers', 'wp-woocommerce-printify-sync');
        $this->supports = [
            'shipping-zones',
            'instance-settings',
        ];

        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title', $this->method_title);
        $this->provider_id = $this->get_option('provider_id');
        $this->provider_name = $this->get_option('provider_name');
        $this->shipping_rates = $this->get_option('shipping_rates', []);

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Initialize form fields
     */
    public function init_form_fields() 
    {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Title', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'default' => $this->method_title,
            ],
            'provider_id' => [
                'title' => __('Provider ID', 'wp-woocommerce-printify-sync'),
                'type' => 'hidden',
            ],
            'provider_name' => [
                'title' => __('Provider Name', 'wp-woocommerce-printify-sync'),
                'type' => 'hidden',
            ],
            'shipping_rates' => [
                'title' => __('Shipping Rates', 'wp-woocommerce-printify-sync'),
                'type' => 'hidden',
            ],
        ];
    }

    /**
     * Calculate shipping
     *
     * @param array $package Package information
     */
    public function calculate_shipping($package = []) 
    {
        if (empty($this->shipping_rates)) {
            return;
        }

        // Get total items in package
        $total_items = array_sum(wp_list_pluck($package['contents'], 'quantity'));

        foreach ($this->shipping_rates as $rate) {
            // Calculate cost based on item count
            $cost = $rate['first_item'];
            if ($total_items > 1) {
                $cost += $rate['additional_items'] * ($total_items - 1);
            }

            // Convert from USD if needed
            if (function_exists('wmc_get_price')) {
                $cost = wmc_get_price($cost);
            }

            // Create rate
            $this->add_rate([
                'id' => $this->id . '_' . sanitize_title($rate['name']),
                'label' => sprintf('%s via %s (%d-%d days)', 
                    $rate['name'],
                    $rate['carrier'],
                    $rate['min_delivery_days'],
                    $rate['max_delivery_days']
                ),
                'cost' => $cost,
                'calc_tax' => 'per_order'
            ]);
        }
    }
}
