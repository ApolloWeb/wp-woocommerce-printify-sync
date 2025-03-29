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
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WPWPS_PLUGIN_FILE', __FILE__);
define('WPWPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPWPS_PLUGIN_VERSION', '1.0.0');
define('WPWPS_TEXT_DOMAIN', 'wp-woocommerce-printify-sync');

// Custom autoloader for the plugin
require_once WPWPS_PLUGIN_DIR . 'src/Core/Autoloader.php';

// Initialize the autoloader
$autoloader = new ApolloWeb\WPWooCommercePrintifySync\Core\Autoloader();
$autoloader->register();

// Initialize the plugin
add_action('plugins_loaded', function() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated!', WPWPS_TEXT_DOMAIN); ?></p>
            </div>
            <?php
        });
        return;
    }
    
    // Initialize the plugin
    $plugin = new ApolloWeb\WPWooCommercePrintifySync\Core\Plugin();
    $plugin->boot();
});