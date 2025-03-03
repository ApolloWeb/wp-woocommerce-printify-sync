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

// Register our custom admin menu
add_action('admin_menu', function() {
    // Remove default WordPress dashboard submenu items to clean up the interface
    remove_submenu_page('index.php', 'index.php');
    remove_submenu_page('index.php', 'update-core.php');
    
    // Add our main menu
    add_menu_page(
        'Printify Sync',
        'Printify Sync',
        'manage_options',
        'printify-sync-dashboard',
        function() {
            include plugin_dir_path(__FILE__) . 'templates/admin/admin-dashboard.php';
        },
        'dashicons-synchronization',
        2
    );
    
    // Add submenu items
    add_submenu_page(
        'printify-sync-dashboard',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'printify-sync-dashboard',
        function() {}
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
        'Sync',
        'Sync',
        'manage_options',
        'printify-sync-sync',
        function() {
            include plugin_dir_path(__FILE__) . 'templates/admin/sync-page.php';
        }
    );
    
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
});

add_action('plugins_loaded', function () {
    Autoloader::register();
    AdminDashboard::register();
    ShopsPage::register();
    ProductImport::register();
    ExchangeRatesPage::register();
    PostmanPage::register();
    SettingsPage::register();
    ProductSync::register();
    OrderSync::register();
    WebhookHandler::register();
    LogCleanup::register();
    NotificationPreferences::register();
    EnvironmentSettings::register();
    EnqueueAssets::register();
});

// Enqueue assets
add_action('admin_enqueue_scripts', function($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'printify-sync') !== false) {
        wp_enqueue_style('printify-sync-admin-css', plugin_dir_url(__FILE__) . 'assets/css/admin-dashboard.css', [], '1.0.0');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.0', true);
        wp_enqueue_script('progressbar-js', 'https://cdn.jsdelivr.net/npm/progressbar.js', [], '1.1.0', true);
        wp_enqueue_script('printify-sync-admin-js', plugin_dir_url(__FILE__) . 'assets/js/admin-dashboard.js', ['jquery', 'chart-js', 'progressbar-js'], '1.0.0', true);
        
        // Font Awesome
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', [], '6.0.0-beta3');
        
        // Google Fonts - Poppins
        wp_enqueue_style('google-fonts-poppins', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', [], null);
    }
});

// Pass user data to JavaScript
add_action('admin_footer', function() {
    if (!is_admin()) return;
    ?>
    <script>
        const printifySyncData = {
            currentDateTime: "2025-03-02 18:45:33",
            currentUser: "ApolloWeb"
        };
    </script>
    <?php
});