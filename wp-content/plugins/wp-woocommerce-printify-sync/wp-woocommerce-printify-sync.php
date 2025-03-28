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

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_PLUGIN_FILE', __FILE__);
define('WPWPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPWPS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WPWPS_ASSETS_URL', WPWPS_PLUGIN_URL . 'assets/');
define('WPWPS_TEMPLATES_DIR', WPWPS_PLUGIN_DIR . 'templates/');
define('WPWPS_LOG_DIR', WPWPS_PLUGIN_DIR . 'logs/');

// Check if WooCommerce is active
function wpwps_is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', array());
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }
    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

// Display admin notice if WooCommerce is not active
function wpwps_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync'); ?></p>
    </div>
    <?php
}

// Initialize the plugin if WooCommerce is active
if (wpwps_is_woocommerce_active()) {
    // Load autoloader
    require_once WPWPS_PLUGIN_DIR . 'src/Core/Autoloader.php';
    
    // Register autoloader
    $autoloader = new ApolloWeb\WPWooCommercePrintifySync\Core\Autoloader();
    $autoloader->register();

    // Initialize plugin
    add_action('plugins_loaded', function() {
        // Load text domain
        load_plugin_textdomain('wp-woocommerce-printify-sync', false, dirname(WPWPS_PLUGIN_BASENAME) . '/languages');
        
        // Initialize the plugin
        $plugin = new ApolloWeb\WPWooCommercePrintifySync\Core\Plugin();
        $plugin->boot();
    });
} else {
    add_action('admin_notices', 'wpwps_woocommerce_missing_notice');
}

// Create necessary directories on plugin activation
register_activation_hook(__FILE__, function() {
    // Create logs directory
    if (!file_exists(WPWPS_LOG_DIR)) {
        wp_mkdir_p(WPWPS_LOG_DIR);
        file_put_contents(WPWPS_LOG_DIR . '.htaccess', 'deny from all');
    }
    
    // Create template cache directory
    if (!file_exists(WPWPS_TEMPLATES_DIR . 'cache')) {
        wp_mkdir_p(WPWPS_TEMPLATES_DIR . 'cache');
        file_put_contents(WPWPS_TEMPLATES_DIR . 'cache/.htaccess', 'deny from all');
    }
});