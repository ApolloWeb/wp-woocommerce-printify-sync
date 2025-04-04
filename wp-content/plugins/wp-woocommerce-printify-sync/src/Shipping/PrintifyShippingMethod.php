<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

class PrintifyShippingMethod extends \WC_Shipping_Method {
    public function __construct($instance_id = 0) {
        $this->id = 'printify_shipping';
        $this->instance_id = absint($instance_id);
        $this->title = __('Printify Shipping', 'wp-woocommerce-printify-sync');
        $this->method_title = __('Printify Shipping', 'wp-woocommerce-printify-sync');
        $this->method_description = __('Shipping rates from Printify print providers', 'wp-woocommerce-printify-sync');
        $this->supports = [
            'shipping-zones',
            'instance-settings',
        ];

        $this->init();
    }

    public function init() {
        $this->init_form_fields();
        $this->init_settings();
    }

    public function init_form_fields() {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Title', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'default' => $this->method_title,
            ],
        ];
    }

    public function calculate_shipping($package = []) {
        // Rates will be calculated dynamically by the ShippingManager
    }
}
