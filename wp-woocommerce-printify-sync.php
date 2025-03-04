<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products from Printify to WooCommerce
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Version: 1.0.7
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

// Set to true to enable debug output
define('PRINTIFY_SYNC_DEBUG', true);

// Simple debugging function
function printify_sync_debug($message) {
    if (defined('PRINTIFY_SYNC_DEBUG') && PRINTIFY_SYNC_DEBUG) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}

printify_sync_debug('Plugin loading: WP WooCommerce Printify Sync');

require_once plugin_dir_path(__FILE__) . 'includes/Autoloader.php';

use ApolloWeb\WPWooCommercePrintifySync\Autoloader;
use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu;
use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminDashboard;
use ApolloWeb\WPWooCommercePrintifySync\Admin\ShopsPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\ProductImport;
use ApolloWeb\WPWooCommercePrintifySync\Admin\ExchangeRatesPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\PostmanPage;
use ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsPage;
use ApolloWeb\WPWooCommercePrintifySync\Sync\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Sync\OrderSync;
use ApolloWeb\WPWooCommercePrintifySync\Webhook\WebhookHandler;
use ApolloWeb\WPWooCommercePrintifySync\Logs\LogCleanup;
use ApolloWeb\WPWooCommercePrintifySync\Settings\NotificationPreferences;
use ApolloWeb\WPWooCommercePrintifySync\Settings\EnvironmentSettings;
use ApolloWeb\WPWooCommercePrintifySync\Utilities\EnqueueAssets;

// Include AJAX handlers
if (file_exists(plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
}

// Register the autoloader
add_action('plugins_loaded', function () {
    Autoloader::register();
    printify_sync_debug('Autoloader registered');
    
    // Register classes
    AdminDashboard::register();
    AdminMenu::register();
    ProductSync::register();
    OrderSync::register();
    WebhookHandler::register();
    LogCleanup::register();
    NotificationPreferences::register();
    EnvironmentSettings::register();
    
    // Register the EnqueueAssets class to handle all asset loading
    EnqueueAssets::register();
    printify_sync_debug('All classes registered');
});

// Instantiate AdminMenu
add_action('plugins_loaded', function () {
    EnqueueAssets::register();
}); // Ensure this runs after the autoloader is registered

