<?php
/*
Plugin Name: WP WooCommerce Printify Sync
Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
Description: WordPress plugin to provide syncing between WooCommerce and Printify.
Version: 1.0.0
Author: ApolloWeb
Author URI: https://github.com/ApolloWeb
License: MIT
License URI: https://opensource.org/licenses/MIT
Text Domain: wp-woocommerce-printify-sync
Domain Path: /languages
Requires at least: 5.0
Tested up to: 5.8
Requires PHP: 7.2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define constants.
define( 'WWPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WWPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include the autoloader.
require_once WWPS_PLUGIN_DIR . 'includes/Autoloader.php';

// Initialize the plugin.
function wwps_init() {
    // Register the admin menu.
    add_action( 'admin_menu', array( 'ApolloWeb\WPWoocomercePrintifySync\Admin', 'register_menu' ) );

    // Register AJAX actions.
    add_action( 'wp_ajax_wwps_get_shops', array( 'ApolloWeb\WPWoocomercePrintifySync\Admin', 'get_shops' ) );
    add_action( 'wp_ajax_wwps_import_products', array( 'ApolloWeb\WPWoocomercePrintifySync\Admin', 'import_products' ) );
}

add_action( 'plugins_loaded', 'wwps_init' );