<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Provides syncing between WooCommerce and Printify.
 * Version: 1.0.0
 * Author: ApolloWeb
 * WC requires at least: 7.0.0
 * WC tested up to: 8.5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define constants.
define('WPWPS_PLUGIN_FILE', __FILE__);
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Autoload classes.
 */
spl_autoload_register(function ($class) {
    $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
    $base_dir = __DIR__ . '/src/';

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

add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

add_action('plugins_loaded', function () {
    // Initialize the plugin.
    $menuManager = new \ApolloWeb\WPWooCommercePrintifySync\Admin\Menu\MenuManager();
    $menuManager->initialize();
});
