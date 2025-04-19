<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Integration between WooCommerce and Printify with advanced features
 * Version: 1.0.0
 * Author: ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 */

// If this file is called directly, abort.
defined('ABSPATH') || exit;

// Define plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_FILE', __FILE__);
define('WPWPS_PATH', plugin_dir_path(__FILE__));
define('WPWPS_URL', plugin_dir_url(__FILE__));

// Autoloader
require_once WPWPS_PATH . 'includes/Autoloader.php';
$autoloader = new \ApolloWeb\WPWooCommercePrintifySync\Autoloader();
$autoloader->register();

// Initialize the plugin - This ensures proper loading order
add_action('plugins_loaded', function() {
    // Initialize the main plugin class after plugins are loaded
    \ApolloWeb\WPWooCommercePrintifySync\Init::register();
});

// DO NOT use translation functions before the init hook!
