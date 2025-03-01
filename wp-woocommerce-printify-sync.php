<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Description: Integrates Printify with WooCommerce for seamless synchronization of product data, orders, categories, images, and SEO metadata.
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://github.com/ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Requires at least: 5.0
 * Tested up to: 5.8
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Autoload classes
require_once plugin_dir_path( __FILE__ ) . 'includes/Autoloader.php';
ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();

// Initialize the plugin
function wp_woocommerce_printify_sync_init() {
    // Load text domain for translations
    load_plugin_textdomain( 'wp-woocommerce-printify-sync', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Initialize admin settings
    new ApolloWeb\WPWooCommercePrintifySync\Admin();

    // Schedule cron jobs
    ApolloWeb\WPWooCommercePrintifySync\ProductImportCron::init();
}
add_action( 'plugins_loaded', 'wp_woocommerce_printify_sync_init' );

// Activation and deactivation hooks
register_activation_hook( __FILE__, 'wp_woocommerce_printify_sync_activate' );
register_deactivation_hook( __FILE__, 'wp_woocommerce_printify_sync_deactivate' );

function wp_woocommerce_printify_sync_activate() {
    // No cron scheduling needed
}

function wp_woocommerce_printify_sync_deactivate() {
    ApolloWeb\WPWooCommercePrintifySync\ProductImportCron::unschedule();
}