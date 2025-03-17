<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

class PrintifyProviderShippingMethod extends \WC_Shipping_Method
{
    private ProviderShippingManager $shippingManager;

    public function __construct($instanceId = 0)
    {
        parent::__construct($instanceId);

        $this->id = 'printify_provider_shipping';
        $this->method_title = __('Printify Provider Shipping', 'wp-woocommerce-printify-sync');
        $this->method_description = __(
            'Provider-specific shipping method for Printify products.',
            'wp-woocommerce-printify-sync'
        );

        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal'
        ];

        $this->init();
    }

    public function calculate_shipping($package = []): void
    {
        $settings = $this->get_option('all');
        $cost = $this->shippingManager->calculateShipping(
            $package,
            $settings['provider_id'],
            $this->instance_id
        );

        if ($cost) {
            $this->add_rate([
                'id' => $this->get_rate_id(),
                'label' => sprintf(
                    __('%s via %s', 'wp-woocommerce-printify-sync'),
                    $this->title,
                    $cost['provider_name']
                ),
                'cost' => $cost['cost'],
                'meta_data' => [
                    'delivery_time' => $cost['delivery_time'],
                    'provider_name' => $cost['provider_name']
                ]
            ]);
        }
    }

    public function admin_options(): void
    {
        // Add custom admin UI for provider shipping method
        ?>
        <h2><?php echo $this->method_title; ?></h2>
        <div class="provider-shipping-info">
            <p><?php echo $this->method_description; ?></p>
            <p class="provider-note">
                <?php _e('Note: This shipping method is specific to a Printify provider. Multiple providers may appear as separate shipping options during checkout.', 'wp-woocommerce-printify-sync'); ?>
            </p>
        </div>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }
}