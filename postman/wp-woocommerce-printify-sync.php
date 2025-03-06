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

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define constants
define('WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION', '1.0.0');
define('WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the autoloader
require_once WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'includes/Autoloader.php';

// Register the autoloader
\ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();

// Include the Enqueue class
require_once WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_DIR . 'includes/Helpers/Enqueue.php';

// Register the Enqueue class
\ApolloWeb\WPWooCommercePrintifySync\Helpers\Enqueue::register();

// Initialize the plugin
function wp_woocommerce_printify_sync_init() {
    // Initialize admin menu
    \ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu::init();
    // Initialize settings page
    \ApolloWeb\WPWooCommercePrintifySync\Admin\SettingsPage::init();
    // Initialize product sync controller
    \ApolloWeb\WPWooCommercePrintifySync\Admin\ProductSyncController::init();
    // Initialize order sync controller
    \ApolloWeb\WPWooCommercePrintifySync\Admin\OrderSyncController::init();
    // Initialize error logs controller
    \ApolloWeb\WPWooCommercePrintifySync\Admin\ErrorLogsController::init();
    // Initialize tickets controller
    \ApolloWeb\WPWooCommercePrintifySync\Admin\TicketsController::init();
    // Initialize postman controller
    \ApolloWeb\WPWooCommercePrintifySync\Admin\PostmanController::init();
    // Initialize exchange rate controller
    \ApolloWeb\WPWooCommercePrintifySync\Admin\ExchangeRateController::init();
    // Initialize sales chart controller
    \ApolloWeb\WPWooCommercePrintifySync\Admin\SalesChartController::init();
}
add_action('plugins_loaded', 'wp_woocommerce_printify_sync_init');