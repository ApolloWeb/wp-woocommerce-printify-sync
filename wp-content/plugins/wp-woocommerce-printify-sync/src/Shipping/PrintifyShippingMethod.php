<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;

/**
 * Printify Shipping Method
 */
class PrintifyShippingMethod extends \WC_Shipping_Method
{
    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Constructor
     *
     * @param int $instance_id Instance ID
     */
    public function __construct($instance_id = 0)
    {
        $this->id = 'printify';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Printify Shipping', 'wp-woocommerce-printify-sync');
        $this->method_description = __('Shipping rates from Printify providers', 'wp-woocommerce-printify-sync');
        $this->supports = [
            'shipping-zones',
            'instance-settings',
        ];

        $this->init();
    }

    /**
     * Initialize shipping method
     */
    public function init(): void
    {
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', $this->method_title);
        $this->logger = new LoggerService('shipping');
    }

    /**
     * Calculate shipping cost
     *
     * @param array $package Package data
     */
    public function calculate_shipping($package = []): void
    {
        if (empty($package['provider_id'])) {
            return;
        }

        $provider_id = $package['provider_id'];
        $shipping_profiles = get_option('wpwps_shipping_profiles', []);
        
        if (!isset($shipping_profiles[$provider_id])) {
            return;
        }

        $profile = $shipping_profiles[$provider_id];
        
        // Get customer location
        $destination_country = $package['destination']['country'];
        
        // Find applicable shipping region
        foreach ($profile['regions'] as $region) {
            if (!in_array($destination_country, $region['countries'])) {
                continue;
            }

            // Calculate cost based on items
            $total_items = array_sum(wp_list_pluck($package['contents'], 'quantity'));
            $first_item_cost = $region['first_item_cost'];
            $additional_item_cost = $region['additional_item_cost'];
            
            $total_cost = $first_item_cost;
            if ($total_items > 1) {
                $total_cost += $additional_item_cost * ($total_items - 1);
            }

            // Convert cost from USD if needed
            if (function_exists('wmc_get_price')) {
                $total_cost = wmc_get_price($total_cost);
            }

            $rate = [
                'id' => $this->id . '_' . $provider_id,
                'label' => $region['name'],
                'cost' => $total_cost,
                'calc_tax' => 'per_order'
            ];

            $this->add_rate($rate);
        }
    }
}
