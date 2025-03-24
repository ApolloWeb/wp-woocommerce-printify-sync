// ...existing code...

add_filter('woocommerce_shipping_methods', function($methods) {
    $methods['printify_provider'] = \ApolloWeb\WPWooCommercePrintifySync\Shipping\PrintifyProviderShipping::class;
    return $methods;
});

// ...existing code...
