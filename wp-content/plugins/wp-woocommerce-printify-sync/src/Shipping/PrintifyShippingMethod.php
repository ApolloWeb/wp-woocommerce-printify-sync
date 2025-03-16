<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

class PrintifyShippingMethod extends \WC_Shipping_Method
{
    private ShippingProfileManager $profileManager;

    public function __construct($instanceId = 0)
    {
        parent::__construct($instanceId);

        $this->id = 'printify_shipping';
        $this->method_title = __('Printify Shipping', 'wp-woocommerce-printify-sync');
        $this->method_description = __(
            'Shipping method for Printify products with real-time rates.',
            'wp-woocommerce-printify-sync'
        );

        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal'
        ];

        $this->init();
    }

    public function init(): void
    {
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');

        add_action(
            'woocommerce_update_options_shipping_' . $this->id,
            [$this, 'process_admin_options']
        );
    }

    public function init_form_fields(): void
    {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Method Title', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'default' => __('Printify Shipping', 'wp-woocommerce-printify-sync')
            ],
            'printify_method_id' => [
                'title' => __('Printify Method ID', 'wp-woocommerce-printify-sync'),
                'type' => 'hidden'
            ],
            'cost' => [
                'title' => __('Base Cost', 'wp-woocommerce-printify-sync'),
                'type' => 'price',
                'default' => '0'
            ],
            'delivery_time' => [
                'title' => __('Delivery Time', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'default' => '5-7 business days'
            ],
            'handling_time' => [
                'title' => __('Handling Time', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'default' => '2-3 business days'
            ]
        ];
    }

    public function calculate_shipping($package = []): void
    {
        $cost = $this->profileManager->calculateShippingCosts(
            $package,
            $this->instance_id
        );

        if ($cost) {
            $rate = [
                'id' => $this->get_rate_id(),
                'label' => $this->title,
                'cost' => $cost['cost'],
                'meta_data' => [
                    'delivery_time' => $cost['delivery_time'],
                    'handling_time' => $cost['handling_time']
                ]
            ];

            $this->add_rate($rate);
        }
    }
}