<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Integration\WooCommerce;

class ShippingMethodRegistration
{
    public function __construct()
    {
        add_action('woocommerce_shipping_init', [$this, 'initShippingMethod']);
        add_filter('woocommerce_shipping_methods', [$this, 'addShippingMethod']);
    }

    public function initShippingMethod(): void
    {
        require_once __DIR__ . '/ShippingMethod.php';
    }

    public function addShippingMethod(array $methods): array
    {
        $methods['printify_shipping'] = PrintifyShippingMethod::class;
        return $methods;
    }
}