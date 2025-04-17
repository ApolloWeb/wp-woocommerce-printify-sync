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

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'WPWPS_VERSION', '1.0.0' );
define( 'WPWPS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPWPS_URL', plugin_dir_url( __FILE__ ) );
define( 'WPWPS_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPWPS_ASSET_PREFIX', 'wpwps-' );
define( 'WPWPS_ASSETS_PATH', WPWPS_PATH . 'assets/' );
define( 'WPWPS_ASSETS_URL', WPWPS_URL . 'assets/' );
define( 'WPWPS_TEMPLATES_PATH', WPWPS_PATH . 'templates/' );
define( 'WPWPS_PRINTIFY_API_URL', 'https://api.printify.com/v1/' );

// Autoloader
require_once WPWPS_PATH . 'includes/Autoloader.php';

/**
 * Load plugin text domain
 */
function wpwps_load_textdomain() {
    load_plugin_textdomain(
        'wp-woocommerce-printify-sync',
        false,
        dirname( WPWPS_BASENAME ) . '/languages'
    );
}
add_action( 'init', 'wpwps_load_textdomain' );

/**
 * Initialize the plugin
 */
function wpwps_init() {
    // Suppress deprecation warnings in development environments
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    }
    
    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            // Use a non-translated message for early notices to prevent translation loading issues
            echo '<div class="notice notice-error"><p>WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.</p></div>';
        });
        return;
    }
    
    // Initialize autoloader
    $autoloader = new ApolloWeb\WPWooCommercePrintifySync\Autoloader();
    $autoloader->register();
    
    // Initialize the plugin
    $plugin = new ApolloWeb\WPWooCommercePrintifySync\Plugin();
    $plugin->init();
}
// Use a later priority to ensure text domain is loaded first
add_action( 'plugins_loaded', 'wpwps_init', 20 );

// Activation hook
register_activation_hook( __FILE__, function() {
    // Create necessary database tables and options
    require_once WPWPS_PATH . 'includes/Activator.php';
    $activator = new ApolloWeb\WPWooCommercePrintifySync\Activator();
    $activator->activate();
});

// Deactivation hook
register_deactivation_hook( __FILE__, function() {
    // Clean up tasks
    require_once WPWPS_PATH . 'includes/Deactivator.php';
    $deactivator = new ApolloWeb\WPWooCommercePrintifySync\Deactivator();
    $deactivator->deactivate();
});
