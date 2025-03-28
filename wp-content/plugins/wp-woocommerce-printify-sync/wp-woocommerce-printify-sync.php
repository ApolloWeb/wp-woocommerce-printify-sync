<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://apolloweb.co/plugins/wp-woocommerce-printify-sync
 * Description: Seamlessly sync your Printify products with WooCommerce
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://apolloweb.co
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 8.2
 *
 * @package WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_FILE', __FILE__);
define('WPWPS_PATH', plugin_dir_path(__FILE__));
define('WPWPS_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>' . __('WP WooCommerce Printify Sync requires WooCommerce to be installed and active.', 'wp-woocommerce-printify-sync') . '</p></div>';
    });
    return;
}

// Load the core class files
require_once WPWPS_PATH . 'src/Core/Autoloader.php';
require_once WPWPS_PATH . 'src/Core/ServiceProvider.php';
require_once WPWPS_PATH . 'src/Core/LibraryLoader.php';
require_once WPWPS_PATH . 'src/Core/Plugin.php';

// Initialize autoloader
$autoloader = new ApolloWeb\WPWooCommercePrintifySync\Core\Autoloader();
$autoloader->register();

// Initialize the plugin
$plugin = ApolloWeb\WPWooCommercePrintifySync\Core\Plugin::getInstance();
$plugin->boot();

// Register activation and deactivation hooks
register_activation_hook(__FILE__, [ApolloWeb\WPWooCommercePrintifySync\Core\Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [ApolloWeb\WPWooCommercePrintifySync\Core\Plugin::class, 'deactivate']);