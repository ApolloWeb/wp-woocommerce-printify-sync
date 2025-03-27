<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Description: Sync products from Printify into WooCommerce, manage them via a dashboard, and handle webhooks.
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://github.com/ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.3
 * License: MIT
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Core/Autoloader.php';
ApolloWeb\WPWooCommercePrintifySync\Core\Autoloader::register();

if (!function_exists('plugin_url')) {
    function plugin_url($path = '') {
        return plugins_url($path, __FILE__);
    }
}

use ApolloWeb\WPWooCommercePrintifySync\Core\Plugin;
(new Plugin())->boot();
