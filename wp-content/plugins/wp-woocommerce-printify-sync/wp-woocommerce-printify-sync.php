<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://apollo-web.co.uk/plugins/wp-woocommerce-printify-sync/
 * Description: Synchronize products between WooCommerce and Printify
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://apollo-web.co.uk/
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('APOLLOWEB_PRINTIFY_VERSION', '1.0.0');
define('APOLLOWEB_PRINTIFY_FILE', __FILE__);
define('APOLLOWEB_PRINTIFY_BASENAME', plugin_basename(__FILE__));
define('APOLLOWEB_PRINTIFY_PATH', plugin_dir_path(__FILE__));
define('APOLLOWEB_PRINTIFY_URL', plugin_dir_url(__FILE__));
define('APOLLOWEB_PRINTIFY_ASSETS_URL', APOLLOWEB_PRINTIFY_URL . 'assets/');

// Logging configuration
// 0 = No logging, 1 = Errors only, 2 = Warnings & Errors, 3 = Info & Warnings & Errors, 4 = All messages
define('APOLLOWEB_PRINTIFY_LOG_LEVEL', 1); // Only log errors

// Redis configuration (if used)
define('APOLLOWEB_PRINTIFY_REDIS_ENABLED', true);
// Rest of the plugin file...