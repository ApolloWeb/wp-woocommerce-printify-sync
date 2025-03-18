<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Automate product syncing, order management, shipping integration, and refunds between Printify and WooCommerce.
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://github.com/ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.3
 * WC requires at least: 5.0.0
 * WC tested up to: 8.0.0
 * License: MIT
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Plugin version
define('WPWPS_VERSION', '1.0.0');

// Plugin file
define('WPWPS_FILE', __FILE__);

// Plugin path
define('WPWPS_PATH', plugin_dir_path(__FILE__));

// Plugin URL
define('WPWPS_URL', plugin_dir_url(__FILE__));

// Plugin basename
define('WPWPS_BASENAME', plugin_basename(__FILE__));

// Ensure WooCommerce is active
function wpwps_is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', []);
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', []));
    }
    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

// Initialize plugin
function wpwps_init() {
    // Check if WooCommerce is active
    if (!wpwps_is_woocommerce_active()) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync') . 
                '</p></div>';
        });
        return;
    }

    // Load text domain
    load_plugin_textdomain('wp-woocommerce-printify-sync', false, dirname(WPWPS_BASENAME) . '/languages');

    // Autoloader
    require_once WPWPS_PATH . 'includes/Autoloader.php';
    \ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();

    // Initialize plugin core
    \ApolloWeb\WPWooCommercePrintifySync\Core\Plugin::instance();
}

// Handle plugin activation
register_activation_hook(WPWPS_FILE, function() {
    require_once WPWPS_PATH . 'includes/Core/Activator.php';
    \ApolloWeb\WPWooCommercePrintifySync\Core\Activator::activate();
});

// Handle plugin deactivation
register_deactivation_hook(WPWPS_FILE, function() {
    require_once WPWPS_PATH . 'includes/Core/Deactivator.php';
    \ApolloWeb\WPWooCommercePrintifySync\Core\Deactivator::deactivate();
});

// Initialize the plugin
add_action('plugins_loaded', 'wpwps_init');
