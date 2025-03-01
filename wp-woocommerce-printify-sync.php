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

/**
 * Code to run during activation.
 */
function activate_wpwps() {
    require_once WPWPS_PLUGIN_DIR . 'includes/Activator.php';
    ApolloWeb\WPWooCommercePrintifySync\Activator::activate();
}

/**
 * Code to run during deactivation.
 */
function deactivate_wpwps() {
    require_once WPWPS_PLUGIN_DIR . 'includes/Deactivator.php';
    ApolloWeb\WPWooCommercePrintifySync\Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wpwps');
register_deactivation_hook(__FILE__, 'deactivate_wpwps');

/**
 * Begin plugin execution.
 */
require_once WPWPS_PLUGIN_DIR . 'vendor/autoload.php';
require_once WPWPS_PLUGIN_DIR . 'includes/Plugin.php';

function run_wpwps() {
    $plugin = new ApolloWeb\WPWooCommercePrintifySync\Plugin();
    $plugin->run();
}
run_wpwps();