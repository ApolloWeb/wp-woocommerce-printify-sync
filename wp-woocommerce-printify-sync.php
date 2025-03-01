<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products from Printify to WooCommerce
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://apolloweb.com
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.3
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPWPS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Require the autoloader
require_once WPWPS_PLUGIN_DIR . 'includes/Autoloader.php';

// Register the autoloader
\ApolloWeb\WooCommercePrintifySync\Autoloader::register();

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // WooCommerce not active - display admin notice
    add_action('admin_notices', function() {
        ?>
        <div class="error">
            <p><?php esc_html_e('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync'); ?></p>
        </div>
        <?php
    });
    return;
}

// Initialize the plugin
function wpwps_init() {
    // Initialize admin
    new \ApolloWeb\WooCommercePrintifySync\Admin();
    
    // Initialize product import cron
    if (class_exists('\ApolloWeb\WooCommercePrintifySync\ProductImportCron')) {
        new \ApolloWeb\WooCommercePrintifySync\ProductImportCron();
    }
    
    // Check if Action Scheduler is loaded
    if (!class_exists('ActionScheduler')) {
        require_once WP_PLUGIN_DIR . '/woocommerce/includes/libraries/action-scheduler/action-scheduler.php';
    }
}

// Hook to plugins_loaded to ensure WooCommerce is loaded first
add_action('plugins_loaded', 'wpwps_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create required database tables if necessary
    // Register initial cron events
    wp_schedule_event(time(), 'daily', 'wpwps_daily_sync');
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up scheduled events
    wp_clear_scheduled_hook('wpwps_daily_sync');
});