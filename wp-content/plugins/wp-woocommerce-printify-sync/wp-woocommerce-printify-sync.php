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

namespace ApolloWeb\WPWooCommercePrintifySync;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPWPS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
    $base_dir = WPWPS_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
add_action('plugins_loaded', function() {
    // Set error reporting for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                 esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync') . 
                 '</p></div>';
        });
        return;
    }

    try {
        // Initialize the plugin
        $plugin = Plugin::getInstance();
        $plugin->init();
    } catch (\Exception $e) {
        error_log('WPWPS Plugin initialization error: ' . $e->getMessage());
        add_action('admin_notices', function() use ($e) {
            echo '<div class="error"><p>Plugin initialization error: ' . esc_html($e->getMessage()) . '</p></div>';
        });
    }
}, 0); // Priority 0 to run early
