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

// FIRST: Disable other menu registrations to prevent duplicates
// This needs to run before plugins_loaded, which is when other classes register their menus
add_action('init', function() {
    // Remove the class register methods that add menu items
    remove_all_actions('admin_menu', 10); // Standard priority for most menu registrations
    
    printify_sync_debug('Removed default admin menu actions');
});

// SECOND: Register our custom admin menu 
add_action('admin_menu', function() {
    // Remove default WordPress dashboard submenu items to clean up the interface
    remove_submenu_page('index.php', 'index.php');
    remove_submenu_page('index.php', 'update-core.php');
    
    // Add our main menu with dashboard icon
    add_menu_page(
        'Printify Sync',
        'Printify Sync',
        'manage_options',
        'printify-sync-dashboard',
        function() {
            printify_sync_debug('Loading dashboard template');
            include plugin_dir_path(__FILE__) . 'templates/admin/admin-dashboard.php';
        },
        'dashicons-dashboard',
        2
    );
    
    // Add submenu items - ONLY THE ONES WE WANT
    add_submenu_page(
        'printify-sync-dashboard',
        'Dashboard', // Page title
        'Dashboard', // Menu title
        'manage_options',
        'printify-sync-dashboard',
        null // We don't need a callback here as the parent menu already has one
    );
    
    add_submenu_page(
        'printify-sync-dashboard',
        'Products',
        'Products',
        'manage_options',
        'printify-sync-products',
        function() {
            include plugin_dir_path(__FILE__) . 'templates/admin/products-page.php';
        }
    );
    
    add_submenu_page(
        'printify-sync-dashboard',
        'Orders',
        'Orders',
        'manage_options',
        'printify-sync-orders',
        function() {
            include plugin_dir_path(__FILE__) . 'templates/admin/orders-page.php';
        }
    );
    
    add_submenu_page(
        'printify-sync-dashboard',
        'Shops',
        'Shops',
        'manage_options',
        'printify-sync-shops',
        function() {
            include plugin_dir_path(__FILE__) . 'templates/admin/shops-page.php';
        }
    );
    
    add_submenu_page(
        'printify-sync-dashboard',
        'Exchange Rates',
        'Exchange Rates',
        'manage_options',
        'printify-sync-exchange-rates',
        function() {
            include plugin_dir_path(__FILE__) . 'templates/admin/exchange-rates-page.php';
        }
    );
    
    // Add Log Viewer submenu item
    add_submenu_page(
        'printify-sync-dashboard',
        'Log Viewer',
        'Log Viewer',
        'manage_options',
        'printify-sync-logs',
        function() {
            include plugin_dir_path(__FILE__) . 'templates/admin/logs-page.php';
        }
    );
    
    // Add Settings under our admin menu instead of WordPress settings
    add_submenu_page(
        'printify-sync-dashboard',
        'Settings',
        'Settings',
        'manage_options',
        'printify-sync-settings',
        function() {
            include plugin_dir_path(__FILE__) . 'templates/admin/settings-page.php';
        }
    );
    
    printify_sync_debug('Admin menu registered');
    
}, 11); // Higher priority to run after potential other menu registrations

add_action('plugins_loaded', function () {
    Autoloader::register();
    printify_sync_debug('Autoloader registered');
    
    // Register these without their menu items
    AdminDashboard::register();
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

// REMOVED: The duplicate printifySyncData declaration from admin_footer