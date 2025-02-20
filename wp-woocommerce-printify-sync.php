<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Description: Integrates Printify with WooCommerce for seamless synchronization of product data, orders, categories, images, and SEO metadata.
 * Version: 1.0.0
 * Author: Rob Owen
 * Author URI: https://github.com/ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 * 
 * User: ApolloWeb
 * Timestamp: 2025-02-20 04:03:56
 */

namespace ApolloWeb\WooCommercePrintifySync;

use ApolloWeb\WooCommercePrintifySync\Admin\AdminSettings;
use ApolloWeb\WooCommercePrintifySync\Includes\OrderSync;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('WPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes.
spl_autoload_register(function ($class) {
    $prefix = 'ApolloWeb\\WooCommercePrintifySync\\';
    $base_dir = WPS_PLUGIN_DIR . 'includes/';

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

// Initialize the plugin.
class WooCommercePrintifySync
{
    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init()
    {
        // Initialize admin settings.
        if (is_admin()) {
            new AdminSettings();
        }

        // Initialize order sync functionality.
        new OrderSync();
    }
}

new WooCommercePrintifySync();