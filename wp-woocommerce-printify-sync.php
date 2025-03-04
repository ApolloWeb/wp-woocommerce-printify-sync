<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Description: WordPress plugin to provide syncing between WooCommerce and Printify
 * Version: 1.0.0
 * Author: ApolloWeb
 * Author URI: https://github.com/ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 7.1
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('PRINTIFY_SYNC_VERSION', '1.0.0');
define('PRINTIFY_SYNC_PATH', plugin_dir_path(__FILE__));
define('PRINTIFY_SYNC_URL', plugin_dir_url(__FILE__));

/**
 * Class loader - a simple autoloader for production environments
 * 
 * @param string $class_name Class name to load
 * @return void
 */
function printify_sync_autoloader($class_name) {
    // Only handle our namespace
    if (strpos($class_name, 'ApolloWeb\\WPWooCommercePrintifySync\\') !== 0) {
        return;
    }

    // Convert namespace to file path
    $file_path = str_replace(
        ['ApolloWeb\\WPWooCommercePrintifySync\\', '\\'],
        ['includes/', '/'],
        $class_name
    ) . '.php';
    
    $file = PRINTIFY_SYNC_PATH . $file_path;
    
    if (file_exists($file)) {
        require_once $file;
    }
}

/**
 * Main class to initialize the plugin
 */
class WP_WooCommerce_Printify_Sync {
    /**
     * Constructor
     */
    public function __construct() {
        // Include files
        $this->includes();
        
        // Initialize actions and hooks
        $this->init();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Include helper functions first so they're available
        require_once PRINTIFY_SYNC_PATH . 'includes/helpers.php';
        
        // Try to include composer autoloader if it exists, otherwise use our own
        $composer_autoload = PRINTIFY_SYNC_PATH . 'vendor/autoload.php';
        if (file_exists($composer_autoload)) {
            require_once $composer_autoload;
        } else {
            spl_autoload_register('printify_sync_autoloader');
        }
        
        // Include core files - direct includes for critical files
        require_once PRINTIFY_SYNC_PATH . 'includes/Admin/AdminMenu.php';
        require_once PRINTIFY_SYNC_PATH . 'includes/Admin/AdminMenuFilter.php';
    }
    
    /**
     * Initialize classes, hooks and actions
     */
    private function init() {
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Initialize admin menu
        add_action('plugins_loaded', function() {
            \ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu::register();
            \ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenuFilter::init();
        });
        
        // Add other initializations here
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        if (!get_option('printify_sync_environment')) {
            add_option('printify_sync_environment', 'production');
        }
        
        // Create tables, set capabilities, etc.
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
}

// Initialize the plugin
new WP_WooCommerce_Printify_Sync();