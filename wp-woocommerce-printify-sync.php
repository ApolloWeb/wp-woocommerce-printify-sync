<?php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
<<<<<<< HEAD
 * Description: WordPress plugin to provide syncing between WooCommerce and Printify
 * Version: 1.0.0
=======
 * Version: 1.0.8
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
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

<<<<<<< HEAD
// Define plugin constants.
define('PRINTIFY_SYNC_VERSION', '1.0.0');
define('PRINTIFY_SYNC_PATH', plugin_dir_path(__FILE__));
define('PRINTIFY_SYNC_URL', plugin_dir_url(__FILE__));
=======
// Define plugin constants
define('PRINTIFY_SYNC_VERSION', '1.0.8');
define('PRINTIFY_SYNC_PATH', plugin_dir_path(__FILE__));
define('PRINTIFY_SYNC_URL', plugin_dir_url(__FILE__));
define('PRINTIFY_SYNC_DEBUG', true);
define('PRINTIFY_SYNC_BASENAME', plugin_basename(__FILE__));
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4

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

<<<<<<< HEAD
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
=======
printify_sync_debug('Plugin loading: WP WooCommerce Printify Sync');

// Helper for consistent user display
function printify_sync_get_current_user() {
    $user = wp_get_current_user();
    return !empty($user->user_login) ? $user->user_login : 'No user';
}

// Helper for consistent datetime display
function printify_sync_get_current_datetime() {
    return gmdate('Y-m-d H:i:s');
}

// Include the autoloader
require_once PRINTIFY_SYNC_PATH . 'includes/Autoloader.php';
\ApolloWeb\WPWooCommercePrintifySync\Autoloader::register();

// Plugin initialization
add_action('plugins_loaded', function () {
    try {
        // Initialize menu
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu')) {
            \ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu::register();
            printify_sync_debug('✅ AdminMenu registered successfully');
        } else {
            printify_sync_debug('❌ AdminMenu class not found');
        }
        
        // Initialize assets
        if (class_exists('ApolloWeb\WPWooCommercePrintifySync\Utilities\EnqueueAssets')) {
            \ApolloWeb\WPWooCommercePrintifySync\Utilities\EnqueueAssets::register();
            printify_sync_debug('✅ EnqueueAssets registered successfully');
        }
        
        // Add admin styles for menu icon
        add_action('admin_head', 'printify_sync_admin_styles');
        
        // Add Font Awesome
        add_action('admin_enqueue_scripts', 'printify_sync_admin_scripts');
        
    } catch (\Exception $e) {
        printify_sync_debug('Error during plugin initialization: ' . $e->getMessage());
    }
});

/**
 * Add CSS for Font Awesome icon in admin menu
 */
function printify_sync_admin_styles() {
    ?>
    <style>
        /* Target the menu icon and replace it with the shirt icon from Font Awesome */
        #adminmenu .toplevel_page_wp-woocommerce-printify-sync .wp-menu-image:before {
            font-family: "Font Awesome 5 Free", "Font Awesome 6 Free" !important;
            content: "\f553" !important; /* fa-shirt icon */
            font-weight: 900;
        }
    </style>
    <?php
}

// Ensure Font Awesome is properly loaded in admin
function printify_sync_admin_scripts() {
    // Enqueue the free version of Font Awesome if not already loaded
    if (!wp_script_is('font-awesome', 'enqueued') && !wp_style_is('font-awesome', 'enqueued')) {
        wp_enqueue_style(
            'font-awesome', 
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 
            [], 
            '6.4.0'
        );
    }
}

// Add plugin action links
add_filter('plugin_action_links_' . PRINTIFY_SYNC_BASENAME, function($links) {
    $dashboard_link = '<a href="' . admin_url('admin.php?page=wp-woocommerce-printify-sync') . '">Dashboard</a>';
    array_unshift($links, $dashboard_link);
    return $links;
});
#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added:     return $links;
#
#
# Commit Hash 16c804f
#
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
