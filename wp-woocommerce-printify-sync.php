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

    // Register AJAX action for sales data
    add_action('wp_ajax_get_sales_data', 'wp_woocommerce_printify_sync_get_sales_data');
}
add_action('plugins_loaded', 'wp_woocommerce_printify_sync_init');

/**
 * Handles AJAX request to get sales data.
 */
function wp_woocommerce_printify_sync_get_sales_data() {
    $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'day';

    // Dummy data for demonstration
    $data = [
        'labels' => [],
        'sales' => []
    ];

    $current_date = new DateTime();
    switch ($filter) {
        case 'day':
            for ($i = 0; $i < 24; $i++) {
                $data['labels'][] = $i . ':00';
                $data['sales'][] = rand(0, 100); // Replace with actual sales data
            }
            break;
        case 'week':
            for ($i = 0; $i < 7; $i++) {
                $data['labels'][] = $current_date->modify('-1 day')->format('Y-m-d');
                $data['sales'][] = rand(0, 100); // Replace with actual sales data
            }
            break;
        case 'month':
            for ($i = 0; $i < 30; $i++) {
                $data['labels'][] = $current_date->modify('-1 day')->format('Y-m-d');
                $data['sales'][] = rand(0, 100); // Replace with actual sales data
            }
            break;
        case 'year':
            for ($i = 0; $i <