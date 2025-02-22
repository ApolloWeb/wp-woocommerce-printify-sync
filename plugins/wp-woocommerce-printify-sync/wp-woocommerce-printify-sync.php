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

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('WPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files.
require_once WPS_PLUGIN_DIR . 'includes/class-api-client.php';
require_once WPS_PLUGIN_DIR . 'includes/class-product-helper.php';
require_once WPS_PLUGIN_DIR . 'includes/class-printify-api.php';
require_once WPS_PLUGIN_DIR . 'includes/helper.php';
require_once WPS_PLUGIN_DIR . 'includes/class-order-sync.php';
require_once WPS_PLUGIN_DIR . 'admin/class-admin.php';

// Initialize the plugin.
function wps_init()
{
    // Initialize admin settings.
    if (is_admin()) {
        new WPS_Admin();
    }

    // Initialize order sync functionality.
    new WPS_Order_Sync();
}
add_action('plugins_loaded', 'wps_init');