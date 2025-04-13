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
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPWPS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WPWPS_PRINTIFY_API_BASE', 'https://api.printify.com/v1/');
define('WPWPS_BATCH_SIZE', 10); // Number of products to process in a batch

// HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Autoloader
spl_autoload_register(function ($class) {
    // Check if the class is part of our namespace
    if (strpos($class, 'ApolloWeb\\WPWooCommercePrintifySync\\') === 0) {
        $relative_class = substr($class, strlen('ApolloWeb\\WPWooCommercePrintifySync\\'));
        $file = WPWPS_PLUGIN_DIR . 'src/' . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Initialize plugin
add_action('plugins_loaded', function () {
    try {
        $bootstrap = new \ApolloWeb\WPWooCommercePrintifySync\Core\Bootstrap();
        $bootstrap->init();
    } catch (\Exception $e) {
        // Critical initialization failure, plugin cannot start
    }
});

// Register activation hook
register_activation_hook(__FILE__, ['\ApolloWeb\WPWooCommercePrintifySync\Core\Activator', 'activate']);

// Register deactivation hook
register_deactivation_hook(__FILE__, ['\ApolloWeb\WPWooCommercePrintifySync\Core\Deactivator', 'deactivate']);

// WP-CLI commands
if (defined('WP_CLI') && WP_CLI) {
    require_once WPWPS_PLUGIN_DIR . 'src/CLI/PrintifyCommands.php';
    \WP_CLI::add_command('printify', '\ApolloWeb\WPWooCommercePrintifySync\CLI\PrintifyCommands');
}
