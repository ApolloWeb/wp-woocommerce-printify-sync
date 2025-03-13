<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products from Printify to WooCommerce
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
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

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin constants
define('WPPS_VERSION', '1.0.0');
define('WPPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPPS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WPPS_PREFIX', 'wpps_');

// Include the autoloader
require_once WPPS_PLUGIN_DIR . 'includes/Autoloader.php';

// Register the autoloader
ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();

// Initialize the plugin
$plugin = ApolloWeb\WPWooCommercePrintifySync\Plugin::getInstance();
$plugin->init();