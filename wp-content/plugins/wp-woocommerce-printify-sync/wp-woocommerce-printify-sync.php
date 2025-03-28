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

if (!defined('ABSPATH')) {
    exit;
}

define('WPWPS_VERSION', '1.0.0');
define('WPWPS_FILE', __FILE__);
define('WPWPS_PATH', plugin_dir_path(__FILE__));
define('WPWPS_URL', plugin_dir_url(__FILE__));

// Load autoloader
require_once WPWPS_PATH . 'src/Core/Autoloader.php';

// Initialize autoloader
$autoloader = new ApolloWeb\WPWooCommercePrintifySync\Core\Autoloader();
$autoloader->register();

// Boot the plugin
add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                 esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync') . 
                 '</p></div>';
        });
        return;
    }
    
    $plugin = new ApolloWeb\WPWooCommercePrintifySync\Core\Plugin();
    $plugin->boot();
});