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

// Define plugin constants
define('PRINTIFY_SYNC_VERSION', '1.0.7');
define('PRINTIFY_SYNC_FILE', __FILE__);
define('PRINTIFY_SYNC_PATH', plugin_dir_path(__FILE__));
define('PRINTIFY_SYNC_URL', plugin_dir_url(__FILE__));
define('PRINTIFY_SYNC_BASENAME', plugin_basename(__FILE__));
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
printify_sync_debug('Plugin path: ' . PRINTIFY_SYNC_PATH);

// Include the autoloader
require_once PRINTIFY_SYNC_PATH . 'includes/Autoloader.php';

// Use our autoloader
\ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();
printify_sync_debug('Autoloader registered');

// Include AJAX handlers
if (file_exists(PRINTIFY_SYNC_PATH . 'includes/ajax-handlers.php')) {
    require_once PRINTIFY_SYNC_PATH . 'includes/ajax-handlers.php';
}

// Initialize components one by one to identify any issues
add_action('plugins_loaded', function() {
    try {
        // Initialize EnqueueAssets first
        if (class_exists('\ApolloWeb\WPWooCommercePrintifySync\Utilities\EnqueueAssets')) {
            printify_sync_debug('Initializing EnqueueAssets');
            \ApolloWeb\WPWooCommercePrintifySync\Utilities\EnqueueAssets::init();
        } else {
            printify_sync_debug('EnqueueAssets class not found');
        }
        
        // Initialize remaining components if they exist
        $classes = [
            '\ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu',
            '\ApolloWeb\WPWooCommercePrintifySync\Admin\AdminDashboard',
            '\ApolloWeb\WPWooCommercePrintifySync\Sync\ProductSync',
            '\ApolloWeb\WPWooCommercePrintifySync\Sync\OrderSync',
            '\ApolloWeb\WPWooCommercePrintifySync\Webhook\WebhookHandler',
            '\ApolloWeb\WPWooCommercePrintifySync\Logs\LogCleanup',
            '\ApolloWeb\WPWooCommercePrintifySync\Settings\NotificationPreferences',
            '\ApolloWeb\WPWooCommercePrintifySync\Settings\EnvironmentSettings'
        ];
        
        foreach ($classes as $class) {
            if (class_exists($class) && method_exists($class, 'register')) {
                printify_sync_debug('Registering: ' . $class);
                call_user_func([$class, 'register']);
            } else {
                printify_sync_debug('Class not found or missing register method: ' . $class);
            }
        }
    } catch (\Exception $e) {
        printify_sync_debug('Error during plugin initialization: ' . $e->getMessage());
    }
});