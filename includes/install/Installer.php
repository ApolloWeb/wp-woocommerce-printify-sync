<?php
/**
 * Installer
 *
 * Handles plugin installation and updates.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Install
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Install;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Installer {
    /**
     * Singleton instance
     *
     * @var Installer
     */
    private static $instance = null;
    
    /**
     * Database version
     *
     * @var string
     */
    private $db_version;
    
    /**
     * Current timestamp
     *
     * @var string
     */
    private $timestamp;
    
    /**
     * Current user
     *
     * @var string
     */
    private $user;
    
    /**
     * Get singleton instance
     *
     * @return Installer
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db_version = '1.0.0';
        $this->timestamp = '2025-03-05 20:34:32';
        $this->user = 'ApolloWeb';
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Check for updates
        add_action('admin_init', array($this, 'check_version'));
        
        // Show admin notice after activation
        add_action('admin_notices', array($this, 'admin_notice_after_activation'));
    }
    
    /**
     * Check version and run updates if necessary
     */
    public function check_version() {
        if (get_option('wpwprintifysync_db_version') != $this->db_version) {
            $this->install();
        }
    }
    
    /**
     * Install the plugin
     */
    public function install() {
        // Create database tables
        $this->create_tables();
        
        // Update version
        update_option('wpwprintifysync_db_version', $this->db_version);
        
        // Log installation
        Logger::get_instance()->info('Plugin installed', array(
            'version' => WPWPRINTIFYSYNC_VERSION,
            'db_version' => $this->db_version,
            'timestamp' => $this->timestamp
        ));
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create logs table
        $table_name = $wpdb->prefix . 'wpwprintifysync_logs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(16) NOT NULL,
            message text NOT NULL,
            context text,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY level (level),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Create product sync history table
        $table_name = $wpdb->prefix . 'wpwprintifysync_product_sync';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            printify_id varchar(64) NOT NULL,
            sync_type varchar(16) NOT NULL,
            status varchar(16) NOT NULL,
            message text,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY printify_id (printify_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Create order sync history table
        $table_name = $wpdb->prefix . 'wpwprintifysync_order_sync';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            printify_id varchar(64) NOT NULL,
            sync_type varchar(16) NOT NULL,
            status varchar(16) NOT NULL,
            message text,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY printify_id (printify_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    public function set_default_options() {
        // Default settings
        $default_settings = array(
            'api_key' => '',
            'shop_id' => '',
            'auto_sync_products' => 'yes',
            'auto_sync_orders' => 'yes',
            'log_level' => 'info',
            'log_retention' => 30,
            'default_product_status' => 'draft',
            'sync_product_images' => 'yes',
            'sync_product_inventory' => 'yes',
            'auto_currency_conversion' => 'yes',
        );
        
        // Only set options if they don't exist
        if (get_option('wpwprintifysync_settings') === false) {
            update_option('wpwprintifysync_settings', $default_settings);
        }
    }
    
    /**
     * Show admin notice after activation
     */
    public function admin_notice_after_activation() {
        // Check if just activated
        if (get_transient('wpwprintifysync_activated')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('WP WooCommerce Printify Sync has been installed. <a href="admin.php?page=printify-sync">Configure the settings</a> to get started!', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
            <?php
            delete_transient('wpwprintifysync_activated');
        }
    }
}