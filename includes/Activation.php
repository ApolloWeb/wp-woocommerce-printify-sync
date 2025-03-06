<?php
/**
 * Activation class handles plugin activation and deactivation
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Utility\Logger;

class Activation {
    /**
     * Plugin activation function
     */
    public static function activate() {
        // Create logs directory
        self::createLogsDirectory();
        
        // Create necessary database tables
        self::createTables();
        
        // Initialize default settings
        self::initializeSettings();
        
        // Create custom post types
        self::registerPostTypes();
        
        // Register custom order statuses
        self::registerOrderStatuses();
        
        // Create necessary files
        self::createFiles();
        
        // Log activation
        $logger = new Logger();
        $logger->info('Plugin activated', [
            'version' => WPWPRINTIFYSYNC_VERSION,
            'time' => current_time('mysql')
        ]);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation function
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wpwprintifysync_update_currency_rates');
        wp_clear_scheduled_hook('wpwprintifysync_sync_stock');
        wp_clear_scheduled_hook('wpwprintifysync_cleanup_logs');
        wp_clear_scheduled_hook('wpwprintifysync_poll_emails');
        
        // Log deactivation
        $logger = new Logger();
        $logger->info('Plugin deactivated', [
            'version' => WPWPRINTIFYSYNC_VERSION,
            'time' => current_time('mysql')
        ]);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create logs directory
     */
    private static function createLogsDirectory() {
        $upload_dir = wp_upload_dir();
        $logs_dir = $upload_dir['basedir'] . '/wp-woocommerce-printify-sync/logs';
        
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
        }
        
        // Create .htaccess file to protect logs
        $htaccess_file = $logs_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Deny access to all files
<FilesMatch \"\">
    Order Allow,Deny
    Deny from all
</FilesMatch>";
            file_put_contents($htaccess_file, $htaccess_content);
        }
        
        // Create index.php to prevent directory listing
        $index_file = $logs_dir . '/index.php';
        if (!file_exists($index_file)) {
            $index_content = "<?php\n// Silence is golden.";
            file_put_contents($index_file, $index_content);
        }
    }
    
    /**
     * Create necessary database tables
     */
    private static function createTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create logs table
        $logs_table = $wpdb->prefix . 'wpwprintifysync_logs';
        $sql = "CREATE TABLE IF NOT EXISTS $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context text,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY level (level),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Create currency rates table
        $rates_table = $wpdb->prefix . 'wpwprintifysync_currency_rates';
        $sql .= "CREATE TABLE IF NOT EXISTS $rates_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            from_currency varchar(3) NOT NULL,
            to_currency varchar(3) NOT NULL,
            rate decimal(10, 6) NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY currency_pair (from_currency, to_currency),
            KEY updated_at (updated_at)
        ) $charset_collate;";
        
        // Create tickets table
        $tickets_table = $wpdb->prefix . 'wpwprintifysync_tickets';
        $sql .= "CREATE TABLE IF NOT EXISTS $tickets_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content text NOT NULL,
            customer_email varchar(255),
            order_id bigint(20),
            status varchar(20) NOT NULL DEFAULT 'open',
            priority varchar(20) NOT NULL DEFAULT 'normal',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY order_id (order_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Execute SQL
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Initialize default settings
     */
    private static function initializeSettings() {
        $default_settings = [
            'api_mode' => 'production',
            'printify_api_key' => '',
            'currency_api_key' => '',
            'geolocation_api_key' => '',
            'log_level' => 'info',
            'log_retention_days' => 14,
            'batch_size' => 20,
            'smtp_enabled' => false,
            'smtp_host' => '',
            'smtp_port' => '',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'pop3_enabled' => false,
            'pop3_host' => '',
            'pop3_port' => '',
            'pop3_username' => '',
            'pop3_password' => '',
            'polling_interval' => 'hourly',
            'currency_update_interval' => 'every_4_hours',
            'stock_sync_interval' => 'twicedaily',
            'notification_email' => get_option('admin_email'),
        ];
        
        // Add settings only if they don't exist
        foreach ($default_settings as $key => $value) {
            $option_name = 'wpwprintifysync_' . $key;
            if (get_option($option_name) === false) {
                update_option($option_name, $value);
            }
        }
    }
    
    /**
     * Register custom post types
     */
    private static function registerPostTypes() {
        // Ticket post type
        register_post_type('wpws_ticket', [
            'labels' => [
                'name' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Support Ticket', 'wp-woocommerce-printify-sync'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'custom-fields'],
            'capability_type' => 'post',
            'hierarchical' => false,
        ]);
    }
    
    /**
     * Register custom order statuses
     */
    private static function registerOrderStatuses() {
        register_post_status('wc-printify-processing', [
            'label' => _x('Printify Processing', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Processing <span class="count">(%s)</span>', 'Printify Processing <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-printify-printed', [
            'label' => _x('Printify Printed', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Printed <span class="count">(%s)</span>', 'Printify Printed <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-printify-shipped', [
            'label' => _x('Printify Shipped', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Shipped <span class="count">(%s)</span>', 'Printify Shipped <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-reprint-requested', [
            'label' => _x('Reprint Requested', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Reprint Requested <span class="count">(%s)</span>', 'Reprint Requested <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-refund-requested', [
            'label' => _x('Refund Requested', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Refund Requested <span class="count">(%s)</span>', 'Refund Requested <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Create necessary files
     */
    private static function createFiles() {
        // Create index.php in plugin directory
        $index_file = WPWPRINTIFYSYNC_PLUGIN_DIR . 'index.php';
        if (!file_exists($index_file)) {
            $index_content = "<?php\n// Silence is golden.";
            file_put_contents($index_file, $index_content);
        }
    }
}