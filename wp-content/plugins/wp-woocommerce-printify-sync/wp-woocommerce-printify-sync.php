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

// Define plugin constants
define('WWPS_VERSION', '2.0.0');
define('WWPS_FILE', __FILE__);
define('WWPS_PATH', plugin_dir_path(__FILE__));
define('WWPS_URL', plugin_dir_url(__FILE__));
define('WWPS_BASENAME', plugin_basename(__FILE__));

// Ensure includes directory exists
$includes_dir = WWPS_PATH . 'includes';
if (!file_exists($includes_dir)) {
    mkdir($includes_dir, 0755, true);
}

// Autoloader - using Composer's autoloader if available, or our custom one
if (file_exists(WWPS_PATH . 'vendor/autoload.php')) {
    require_once WWPS_PATH . 'vendor/autoload.php';
} else {
    require_once WWPS_PATH . 'includes/Autoloader.php';
    \ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();
    require_once WWPS_PATH . 'includes/API/WebhookEndpoints.php'; // Ensure WebhookEndpoints is included
}

// Check if WooCommerce is active
function wwps_is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', array());
    
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }
    
    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

// Initialize the plugin
function wwps_init() {
    if (!wwps_is_woocommerce_active()) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                __('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync') . 
                '</p></div>';
        });
        return;
    }
    
    // Load text domain
    load_plugin_textdomain('wp-woocommerce-printify-sync', false, dirname(WWPS_BASENAME) . '/languages');
    
    // Initialize the plugin
    \ApolloWeb\WPWooCommercePrintifySync\Core\Plugin::instance();
    
    // Register Enqueue class
    \ApolloWeb\WPWooCommercePrintifySync\Core\Enqueue::register();

    // Register Webhook Endpoints
    $webhook_endpoints = new \ApolloWeb\WPWooCommercePrintifySync\API\WebhookEndpoints();
    $webhook_endpoints->register();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, function() {
    require_once WWPS_PATH . 'includes/Core/Activator.php';
    \ApolloWeb\WPWooCommercePrintifySync\Core\Activator::activate();
});

register_deactivation_hook(__FILE__, function() {
    require_once WWPS_PATH . 'includes/Core/Deactivator.php';
    \ApolloWeb\WPWooCommercePrintifySync\Core\Deactivator::deactivate();
});

// Hook into WordPress init action to initialize our plugin
add_action('plugins_loaded', 'wwps_init');

