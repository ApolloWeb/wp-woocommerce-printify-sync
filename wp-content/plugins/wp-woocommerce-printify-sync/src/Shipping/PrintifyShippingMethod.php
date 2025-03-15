<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

class PrintifyShippingMethod extends \WC_Shipping_Method
{
    private string $currentTime = '2025-03-15 19:01:40';
    private string $currentUser = 'ApolloWeb';

    public function __construct($instance_id = 0)
    {
        $this->id = 'printify_calculated';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Printify Calculated Shipping', 'wp-woocommerce-printify-sync');
        $this->method_description = __('Dynamically calculated shipping rates from Printify', 'wp-woocommerce-printify-sync');
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        $this->init();
    }

    public function init(): void
    {
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields(): void
    {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Method Title', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wp-woocommerce-printify-sync'),
                'default' => __('Printify Shipping', 'wp-woocommerce-printify-sync'),
                'desc_tip' => true,
            ],
            'printify_rate_id' => [
                'title' => __('Printify Rate ID', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => __('The Printify shipping rate ID.', 'wp-woocommerce-printify-sync'),
                'desc_tip' => true,
            ],
        ];
    }

    public function calculate_shipping($package = []): void
    {
        $rate = [
            'id' => $this->get_rate_id(),
            'label' => $this->get_option('title'),
            'cost' => $this->calculate_cost($package),
            'calc_tax' => 'per_order'
        ];

        $this->add_rate($rate);
    }

    private function calculate_cost(array $package): float
    {
        // Implementation for dynamic cost calculation based on Printify API
        // This is a placeholder - actual implementation would need to consider:
        // - Product weights
        // - Shipping zones
        // - Printify provider locations
        return 0.00;
    }
}