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

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_FILE', __FILE__);
define('WPWPS_PATH', plugin_dir_path(__FILE__));
define('WPWPS_URL', plugin_dir_url(__FILE__));
define('WPWPS_BASENAME', plugin_basename(__FILE__));
define('WPWPS_ASSETS_URL', WPWPS_URL . 'assets/');
define('WPWPS_TEMPLATES_PATH', WPWPS_PATH . 'templates/');
define('WPWPS_CACHE_PATH', WPWPS_PATH . 'cache/');

// Include the necessary files for AJAX handling
require_once plugin_dir_path(__FILE__) . 'src/Core/Autoloader.php';
require_once plugin_dir_path(__FILE__) . 'src/Helpers/View.php';
require_once plugin_dir_path(__FILE__) . 'src/Providers/DashboardProvider.php';
require_once plugin_dir_path(__FILE__) . 'src/Providers/SettingsProvider.php';

// Ensure admin-ajax.php is used for AJAX requests
add_action('wp_ajax_nopriv_test_ajax_action', 'test_ajax_action_handler');
add_action('wp_ajax_test_ajax_action', 'test_ajax_action_handler');

function test_ajax_action_handler() {
    check_ajax_referer('wpwps-admin-nonce', 'nonce');

    // Example response
    wp_send_json_success(['message' => 'AJAX request successful']);
}

add_action('wp_ajax_save_settings', function() {
    check_ajax_referer('wpwps-admin-nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $settings = $_POST['settings'] ?? [];
    update_option('wpwps_settings', $settings);

    wp_send_json_success(['message' => 'Settings saved successfully']);
});

// Initialize autoloader
$autoloader = new \ApolloWeb\WPWooCommercePrintifySync\Core\Autoloader();
$autoloader->register();

// Boot the plugin
add_action('plugins_loaded', function() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                 esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync') . 
                 '</p></div>';
        });
        return;
    }
    
    $plugin = new \ApolloWeb\WPWooCommercePrintifySync\Core\Plugin();
    $plugin->boot();
});