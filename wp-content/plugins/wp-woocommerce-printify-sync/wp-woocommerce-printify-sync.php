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

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPWPS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load the autoloader
require_once WPWPS_PLUGIN_DIR . 'src/Autoloader.php';
\ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();

// Register custom cron schedules
add_filter('cron_schedules', function($schedules) {
    $schedules['wpwps_stock_sync'] = [
        'interval' => 6 * HOUR_IN_SECONDS,
        'display' => __('Every 6 hours', 'wp-woocommerce-printify-sync')
    ];
    return $schedules;
});

// Load the plugin container and bootstrap
$plugin = \ApolloWeb\WPWooCommercePrintifySync\Plugin::getInstance();
$plugin->boot();

// Activation/deactivation hooks
register_activation_hook(__FILE__, [$plugin, 'activate']);
register_deactivation_hook(__FILE__, [$plugin, 'deactivate']);
register_uninstall_hook(__FILE__, ['\ApolloWeb\WPWooCommercePrintifySync\Plugin', 'uninstall']);
