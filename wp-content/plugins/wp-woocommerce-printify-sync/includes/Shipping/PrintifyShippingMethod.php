<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

class PrintifyShippingMethod extends \WC_Shipping_Method {
    public function __construct($instance_id = 0) {
        parent::__construct($instance_id);
        
        $this->id = 'wpwps_printify_shipping';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Printify Shipping', 'wp-woocommerce-printify-sync');
        $this->method_description = __('Dynamic shipping rates from Printify providers', 'wp-woocommerce-printify-sync');
        $this->supports = [
            'shipping-zones',
            'instance-settings'
        ];

        $this->init();
    }

    public function init(): void {
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', $this->method_title);
        $this->provider_id = $this->get_option('provider_id');
        $this->rates = $this->get_option('rates', []);

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields(): void {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Method Title', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wp-woocommerce-printify-sync'),
                'default' => $this->method_title,
                'desc_tip' => true
            ],
            'provider_id' => [
                'title' => __('Provider ID', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => __('Printify provider ID for this shipping method.', 'wp-woocommerce-printify-sync'),
                'desc_tip' => true
            ],
            'rates' => [
                'title' => __('Shipping Rates', 'wp-woocommerce-printify-sync'),
                'type' => 'textarea',
                'description' => __('Shipping rates configuration in JSON format.', 'wp-woocommerce-printify-sync'),
                'desc_tip' => true,
                'default' => '[]'
            ]
        ];
    }

    public function calculate_shipping($package = []): array {
        if (empty($this->rates)) {
            return [];
        }

        $rates = [];
        $total_quantity = $package['total_quantity'] ?? 0;

        foreach ($this->rates as $rate) {
            if ($this->rate_applies($rate, $total_quantity)) {
                $shipping_rate = [
                    'id' => $this->get_rate_id($rate),
                    'label' => $rate['label'] ?? $this->title,
                    'cost' => $this->calculate_rate_cost($rate, $total_quantity),
                    'calc_tax' => 'per_order'
                ];

                if (!empty($rate['meta_data'])) {
                    $shipping_rate['meta_data'] = $rate['meta_data'];
                }

                $rates[] = $shipping_rate;
            }
        }

        return empty($rates) ? [] : $rates[0]; // Return cheapest applicable rate
    }

    private function rate_applies(array $rate, int $quantity): bool {
        if (isset($rate['min_quantity']) && $quantity < $rate['min_quantity']) {
            return false;
        }

        if (isset($rate['max_quantity']) && $quantity > $rate['max_quantity']) {
            return false;
        }

        return true;
    }

    private function calculate_rate_cost(array $rate, int $quantity): float {
        $base_cost = floatval($rate['base_cost'] ?? 0);
        $per_item_cost = floatval($rate['per_item_cost'] ?? 0);
        
        $total = $base_cost + ($per_item_cost * $quantity);

        // Apply any discounts
        if (!empty($rate['discounts'])) {
            foreach ($rate['discounts'] as $discount) {
                if ($quantity >= $discount['min_quantity']) {
                    if (!empty($discount['fixed_amount'])) {
                        $total -= $discount['fixed_amount'];
                    } elseif (!empty($discount['percentage'])) {
                        $total *= (1 - ($discount['percentage'] / 100));
                    }
                }
            }
        }

        return max(0, $total);
    }

    private function get_rate_id(array $rate): string {
        return $this->id . ':' . $this->instance_id . ':' . (
            $rate['id'] ?? md5(json_encode($rate))
        );
    }

    public function is_available($package): bool {
        // Check if this shipping method is enabled for the current cart
        if (!parent::is_available($package)) {
            return false;
        }

        // Check if we have any applicable rates
        return !empty($this->calculate_shipping($package));
    }
}