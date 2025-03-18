<?php

/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Automate product syncing, order management, shipping integration, and refunds between Printify and WooCommerce.
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Version: 2.0.0
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

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Use our own autoloader.
require_once __DIR__ . '/src/Autoloader.php';
\ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();

use ApolloWeb\WPWooCommercePrintifySync\Plugin;

function wp_woocommerce_printify_sync_run() {
    $plugin = new Plugin();
    $plugin->run();
}

wp_woocommerce_printify_sync_run();
