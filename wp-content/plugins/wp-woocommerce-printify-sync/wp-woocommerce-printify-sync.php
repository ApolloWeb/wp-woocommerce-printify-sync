<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://apolloweb.dev/plugins/wp-woocommerce-printify-sync
 * Description: Advanced integration between WooCommerce and Printify with automated syncing, AI-powered support, and modern dashboard.
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://apolloweb.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires PHP: 8.2
 * Requires at least: 6.0
 * WC requires at least: 7.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin version
define('WPWPS_VERSION', '1.0.0');

// Plugin paths
define('WPWPS_PLUGIN_FILE', __FILE__);
define('WPWPS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPWPS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Simple class autoloader
spl_autoload_register(function($class) {
    $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
    $base_dir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Check WooCommerce dependency
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>' . 
             esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync') . 
             '</p></div>';
    });
    return;
}

// Bootstrap plugin on init to ensure WooCommerce is loaded
add_action('init', function() {
    $app = new ApolloWeb\WPWooCommercePrintifySync\Core\Application();
    
    // Register providers
    $app->register(ApolloWeb\WPWooCommercePrintifySync\Providers\DashboardProvider::class);
    
    // Boot application
    $app->boot();
    
    do_action('wpwps_loaded', $app);
}, 20);

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create necessary database tables
    require_once WPWPS_PLUGIN_PATH . 'src/Database/Installer.php';
    $installer = new ApolloWeb\WPWooCommercePrintifySync\Database\Installer();
    $installer->install();
    
    // Schedule cron events
    if (!wp_next_scheduled('wpwps_sync_products')) {
        wp_schedule_event(time(), 'hourly', 'wpwps_sync_products');
    }
    
    // Set default options
    update_option('wpwps_version', WPWPS_VERSION);
    
    // Plugin activated action
    do_action('wpwps_activated');
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clear scheduled events
    wp_clear_scheduled_hook('wpwps_sync_products');
    wp_clear_scheduled_hook('wpwps_process_email_queue');
    
    // Plugin deactivated action
    do_action('wpwps_deactivated');
});

// Uninstall hook (must be a static function)
register_uninstall_hook(__FILE__, ['ApolloWeb\\WPWooCommercePrintifySync\\Uninstaller', 'uninstall']);
