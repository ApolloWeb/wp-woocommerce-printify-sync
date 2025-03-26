<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

class PrintifyShipping extends \WC_Shipping_Method
{
    private const OPTION_PREFIX = 'wpwps_';

    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->id = 'printify';
        $this->method_title = 'Printify Shipping';
        $this->method_description = 'Dynamic shipping rates from Printify providers';
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

        $this->title = $this->get_option('title', 'Printify Shipping');
        $this->cost = $this->get_option('cost', 0);
        $this->additional_cost = $this->get_option('additional_cost', 0);

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields(): void
    {
        $this->instance_form_fields = [
            'title' => [
                'title' => 'Method Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'Printify Shipping',
                'desc_tip' => true
            ],
            'cost' => [
                'title' => 'Base Cost',
                'type' => 'price',
                'description' => 'Cost for the first item',
                'default' => '0',
                'desc_tip' => true
            ],
            'additional_cost' => [
                'title' => 'Additional Item Cost',
                'type' => 'price',
                'description' => 'Cost for each additional item',
                'default' => '0',
                'desc_tip' => true
            ]
        ];
    }

    public function calculate_shipping($package = []): void
    {
        $cost = $this->get_option('cost');
        $items_count = array_sum(wp_list_pluck($package['contents'], 'quantity')) - 1;
        
        if ($items_count > 0) {
            $cost += ($items_count * $this->get_option('additional_cost'));
        }

        $rate = [
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => $cost,
            'package' => $package,
        ];

        $this->add_rate($rate);
    }

    private function get_rate_id(): string
    {
        $method_id = $this->is_zone_based() ? $this->get_instance_id() : $this->id;
        return $this->id . ':' . $method_id;
    }

    private function is_zone_based(): bool
    {
        return !empty($this->instance_id);
    }
}