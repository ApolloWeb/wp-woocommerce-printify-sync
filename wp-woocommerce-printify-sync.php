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
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPWPRINTIFYSYNC_VERSION', '1.0.0');
define('WPWPRINTIFYSYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPRINTIFYSYNC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPWPRINTIFYSYNC_PLUGIN_FILE', __FILE__);
define('WPWPRINTIFYSYNC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include helpers and core files
require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/helpers/CoreHelper.php';

// Initialize the plugin
function wpwprintifysync_init() {
    // Check WooCommerce dependency
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', function() {
            ?>
            <div class="error">
                <p><?php _e('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
            <?php
        });
        return;
    }
    
    // Load plugin core
    ApolloWeb\WPWooCommercePrintifySync\Helpers\CoreHelper::getInstance()->init();
}
add_action('plugins_loaded', 'wpwprintifysync_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync'));
    }
    
    // Create necessary folders and files
    $upload_dir = wp_upload_dir();
    $logs_dir = $upload_dir['basedir'] . '/wp-woocommerce-printify-sync/logs';
    
    if (!file_exists($logs_dir)) {
        wp_mkdir_p($logs_dir);
    }
    
    // Log activation with date/time
    $log_file = $logs_dir . '/activation.log';
    $log_message = sprintf(
        "[%s] WP WooCommerce Printify Sync activated by %s (Version: %s)\n",
        '2025-03-05 17:53:44', // Using provided current time
        'ApolloWeb', // Using provided login
        WPWPRINTIFYSYNC_VERSION
    );
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Initialize default options
    update_option('wpwprintifysync_api_mode', 'production', false);
    update_option('wpwprintifysync_log_level', 'info', false);
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Log deactivation
    $upload_dir = wp_upload_dir();
    $logs_dir = $upload_dir['basedir'] . '/wp-woocommerce-printify-sync/logs';
    $log_file = $logs_dir . '/activation.log';
    
    $log_message = sprintf(
        "[%s] WP WooCommerce Printify Sync deactivated by %s\n",
        current_time('Y-m-d H:i:s'),
        wp_get_current_user()->user_login
    );
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
});