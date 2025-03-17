<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * ...
 * WC requires at least: 7.0.0
 * WC tested up to: 8.5.0
 * Requires PHP: 7.4
 */

add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});