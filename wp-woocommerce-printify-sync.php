<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products from Printify to WooCommerce
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Version: 1.0.8
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

// Define plugin constants
define('PRINTIFY_SYNC_VERSION', '1.0.8');
define('PRINTIFY_SYNC_PATH', plugin_dir_path(__FILE__));
define('PRINTIFY_SYNC_URL', plugin_dir_url(__FILE__));
define('PRINTIFY_SYNC_DEBUG', true);
define('PRINTIFY_SYNC_BASENAME', plugin_basename(__FILE__));

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

// Helper for consistent user display
function printify_sync_get_current_user() {
    $user = wp_get_current_user();
    return !empty($user->user_login) ? $user->user_login : 'No user';
}

// Helper for consistent datetime display
function printify_sync_get_current_datetime() {
    return gmdate('Y-m-d H:i:s');
}

// Include the autoloader
require_once PRINTIFY_SYNC_PATH . 'includes/Autoloader.php';
\ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();

// Plugin initialization
add_action('plugins_loaded', function () {
    try {
        // Initialize menu
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu')) {
            \ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu::register();
            printify_sync_debug('✅ AdminMenu registered successfully');
        } else {
            printify_sync_debug('❌ AdminMenu class not found');
        }
        
        // Initialize assets
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Utilities\EnqueueAssets')) {
            \ApolloWeb\WPWooCommercePrintifySync\Utilities\EnqueueAssets::register();
            printify_sync_debug('✅ EnqueueAssets registered successfully');
        }
        
        // Add admin styles for menu icon
        add_action('admin_head', 'printify_sync_admin_styles');
        
        // Add Font Awesome
        add_action('admin_enqueue_scripts', 'printify_sync_admin_scripts');
        
    } catch (\Exception $e) {
        printify_sync_debug('Error during plugin initialization: ' . $e->getMessage());
    }
});

/**
 * Add CSS for Font Awesome icon in admin menu
 */
function printify_sync_admin_styles() {
    ?>
    <style>
        /* Target the menu icon and replace it with the shirt icon from Font Awesome */
        #adminmenu .toplevel_page_wp-woocommerce-printify-sync .wp-menu-image:before {
            font-family: "Font Awesome 5 Free", "Font Awesome 6 Free" !important;
            content: "\f553" !important; /* fa-shirt icon */
            font-weight: 900;
        }
    </style>
    <?php
}

// Ensure Font Awesome is properly loaded in admin
function printify_sync_admin_scripts() {
    // Enqueue the free version of Font Awesome if not already loaded
    if (!wp_script_is('font-awesome', 'enqueued') && !wp_style_is('font-awesome', 'enqueued')) {
        wp_enqueue_style(
            'font-awesome', 
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 
            [], 
            '6.4.0'
        );
    }
}

// Add plugin action links
add_filter('plugin_action_links_' . PRINTIFY_SYNC_BASENAME, function($links) {
    $dashboard_link = '<a href="' . admin_url('admin.php?page=wp-woocommerce-printify-sync') . '">Dashboard</a>';
    array_unshift($links, $dashboard_link);
    return $links;
});
#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added:     return $links;
#
#
# Commit Hash 16c804f
#
