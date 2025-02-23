<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Description: Integrates Printify with WooCommerce for seamless synchronization of product data, orders, categories, images, and SEO metadata.
 * Version: 1.0.0
 * Author: Rob Owen
 * Author URI: https://github.com/ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 */

namespace ApolloWeb\WooCommercePrintifySync;

use ApolloWeb\WooCommercePrintifySync\Admin;
use ApolloWeb\WooCommercePrintifySync\OrderSync;
use ApolloWeb\WooCommercePrintifySync\PluginDependencies;
use ApolloWeb\WooCommercePrintifySync\UploadHandler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'WPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoloader expecting file names to mirror class names.
spl_autoload_register(function($class) {
    $prefix = __NAMESPACE__ . '\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $directories = [
        __DIR__ . '/includes/',
        __DIR__ . '/admin/',
    ];
    
    foreach ($directories as $base_dir) {
        $file = $base_dir . str_replace('\\','/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Initialize UploadHandler
new UploadHandler();

// Initialize Plugin Dependencies
new PluginDependencies();

// Initialize the Admin settings page.
new Admin();

// Initialize OrderSync.
new OrderSync();