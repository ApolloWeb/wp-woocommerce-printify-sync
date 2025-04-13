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

namespace ApolloWeb\WPWooCommercePrintifySync;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WPWPS_VERSION', '1.0.0');
define('WPWPS_FILE', __FILE__);
define('WPWPS_PATH', plugin_dir_path(WPWPS_FILE));
define('WPWPS_URL', plugin_dir_url(WPWPS_FILE));
define('WPWPS_BASENAME', plugin_basename(WPWPS_FILE));

/**
 * Main plugin bootstrap
 */
class Bootstrap {
    /**
     * Initialize the plugin
     */
    public static function init(): void {
        // Check requirements
        if (!self::checkRequirements()) {
            return;
        }
        
        // Register autoloader
        self::registerAutoloader();
        
        // Register activation/deactivation hooks
        register_activation_hook(WPWPS_FILE, [__CLASS__, 'activate']);
        register_deactivation_hook(WPWPS_FILE, [__CLASS__, 'deactivate']);
        
        // Initialize the core plugin
        add_action('plugins_loaded', [__CLASS__, 'loadPlugin']);
    }
    
    /**
     * Check if the plugin requirements are met
     * 
     * @return bool True if requirements are met, false otherwise
     */
    private static function checkRequirements(): bool {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.3', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . 
                    esc_html__('WP WooCommerce Printify Sync requires PHP 7.3 or higher.', 'wp-woocommerce-printify-sync') . 
                    '</p></div>';
            });
            return false;
        }
        
        // Check WooCommerce
        add_action('admin_init', function() {
            if (!class_exists('WooCommerce')) {
                add_action('admin_notices', function() {
                    echo '<div class="error"><p>' . 
                        esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync') . 
                        '</p></div>';
                });
            }
        });
        
        return true;
    }
    
    /**
     * Register the autoloader
     */
    private static function registerAutoloader(): void {
        spl_autoload_register(function ($class) {
            // Check if the class belongs to our namespace
            $prefix = 'ApolloWeb\\WPWooCommercePrintifySync\\';
            $base_dir = __DIR__ . '/src/';
            
            // Does the class use the namespace prefix?
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                // No, move to the next registered autoloader
                return;
            }
            
            // Get the relative class name
            $relative_class = substr($class, $len);
            
            // Replace namespace separators with directory separators
            // and append with .php
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            
            // If the file exists, require it
            if (file_exists($file)) {
                require_once $file;
            }
        });
    }
    
    /**
     * Load the plugin
     */
    public static function loadPlugin(): void {
        // Initialize the core plugin
        Core\PluginCore::getInstance();
    }
    
    /**
     * Plugin activation
     */
    public static function activate(): void {
        // Create necessary database tables and options
        update_option('wpwps_version', WPWPS_VERSION);
        
        // Initialize custom tables
        self::initializeDatabase();
        
        // Schedule events
        self::scheduleEvents();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate(): void {
        // Unschedule events
        wp_clear_scheduled_hook('wpwps_product_sync');
        wp_clear_scheduled_hook('wpwps_daily_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Initialize database tables
     */
    private static function initializeDatabase(): void {
        // Create custom tables if needed
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for sync history
        $table_sync_history = $wpdb->prefix . 'wpwps_sync_history';
        $sql_sync_history = "CREATE TABLE $table_sync_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sync_type varchar(50) NOT NULL,
            start_time datetime NOT NULL,
            end_time datetime DEFAULT NULL,
            status varchar(20) NOT NULL,
            items_total int(11) DEFAULT 0,
            items_synced int(11) DEFAULT 0,
            items_failed int(11) DEFAULT 0,
            log_data longtext DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Table for product mapping
        $table_product_mapping = $wpdb->prefix . 'wpwps_product_mapping';
        $sql_product_mapping = "CREATE TABLE $table_product_mapping (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wc_product_id bigint(20) NOT NULL,
            printify_product_id varchar(50) NOT NULL,
            last_sync datetime DEFAULT NULL,
            sync_status varchar(20) DEFAULT 'not_synced',
            sync_error text DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY wc_product_id (wc_product_id),
            KEY printify_product_id (printify_product_id)
        ) $charset_collate;";
        
        // Import dbDelta function
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Create tables
        dbDelta($sql_sync_history);
        dbDelta($sql_product_mapping);
    }
    
    /**
     * Schedule recurring events
     */
    private static function scheduleEvents(): void {
        // Schedule daily product sync at midnight
        if (!wp_next_scheduled('wpwps_product_sync')) {
            wp_schedule_event(strtotime('midnight'), 'daily', 'wpwps_product_sync');
        }
        
        // Schedule daily cleanup for logs, temporary files, etc
        if (!wp_next_scheduled('wpwps_daily_cleanup')) {
            wp_schedule_event(strtotime('midnight') + 3600, 'daily', 'wpwps_daily_cleanup');
        }
    }
}

// Initialize the plugin
Bootstrap::init();
