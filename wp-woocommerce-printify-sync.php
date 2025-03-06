<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products from Printify to WooCommerce
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Version: 1.1.1
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

// Define plugin constants
define('WPWPPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPPS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the autoloader
require_once WPWPPS_PLUGIN_DIR . 'includes/Autoloader.php';

// Initialize the Enqueue class
new ApolloWeb\WPWooCommercePrintifySync\Enqueue();