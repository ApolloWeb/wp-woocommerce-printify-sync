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

defined('ABSPATH') || exit;

// Define plugin constants
define('PRINTIFY_SYNC_VERSION', '1.0.0');
define('PRINTIFY_SYNC_FILE', __FILE__);
define('PRINTIFY_SYNC_PATH', plugin_dir_path(__FILE__));
define('PRINTIFY_SYNC_URL', plugin_dir_url(__FILE__));
define('PRINTIFY_SYNC_BASENAME', plugin_basename(__FILE__));

// Register autoloader
require_once PRINTIFY_SYNC_PATH . 'src/Autoloader.php';
\ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();

use ApolloWeb\WPWooCommercePrintifySync\Admin;
use ApolloWeb\WPWooCommercePrintifySync\Http\WordPressHttpClient;
use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;

// Initialize plugin
function printify_sync_init() {
    // Load text domain
    load_plugin_textdomain(
        'wp-woocommerce-printify-sync',
        false,
        dirname(PRINTIFY_SYNC_BASENAME) . '/languages'
    );

    // Initialize HTTP client and API
    $http_client = new WordPressHttpClient();
    $api = new PrintifyAPI($http_client);

    // Initialize settings page
    $settings = new Admin\Settings($api);
    $settings->init();
}

// Hook into WordPress
add_action('plugins_loaded', 'printify_sync_init');