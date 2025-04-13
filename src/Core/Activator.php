<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Fired during plugin activation
 */
class Activator {
    /**
     * Plugin activation logic
     *
     * @return void
     */
    public static function activate() {
        // Create necessary database tables
        self::create_tables();
        
        // Register required webhooks with Printify
        self::register_webhooks();
        
        // Schedule recurring sync check
        if (!wp_next_scheduled('wpwps_daily_sync_check')) {
            wp_schedule_event(time(), 'daily', 'wpwps_daily_sync_check');
        }
        
        // Schedule cleanup of old logs
        if (!wp_next_scheduled('wpwps_cleanup_logs')) {
            wp_schedule_event(time(), 'weekly', 'wpwps_cleanup_logs');
        }
        
        // Set flag for first-time welcome screen
        if (!get_option('wpwps_installed')) {
            add_option('wpwps_installed', time());
            add_option('wpwps_show_welcome', true);
        }
        
        // Bump the version
        update_option('wpwps_version', WPWPS_VERSION);
        
        // Flush rewrite rules to ensure our endpoints work
        flush_rewrite_rules();
    }
    
    /**
     * Create custom tables needed for the plugin
     *
     * @return void
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create sync log table
        $table_name = $wpdb->prefix . 'printify_sync_logs';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            action varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text NOT NULL,
            product_id bigint(20) NULL,
            printify_id varchar(50) NULL,
            details longtext NULL,
            PRIMARY KEY  (id),
            KEY action (action),
            KEY status (status),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        // Create webhook events table
        $webhook_table = $wpdb->prefix . 'printify_webhook_events';
        $webhook_sql = "CREATE TABLE $webhook_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(100) NOT NULL,
            payload longtext NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            processed tinyint(1) DEFAULT 0 NOT NULL,
            attempts int(11) DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY processed (processed)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($webhook_sql);
    }
    
    /**
     * Register necessary webhooks with Printify
     *
     * @return void
     */
    private static function register_webhooks() {
        $settings_service = new \ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsService();
        $printify_settings = $settings_service->getPrintifySettings();
        
        if (empty($printify_settings['api_key']) || empty($printify_settings['shop_id'])) {
            return; // Cannot register webhooks without API key and shop ID
        }
        
        $api = new \ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApi(
            $printify_settings['api_key'],
            $printify_settings['api_endpoint']
        );
        
        // Get webhook URL
        $webhook_url = rest_url('wpwps/v1/webhook');
        
        // Register necessary webhooks
        $webhooks = [
            'product.update',
            'product.delete',
            'order.created',
            'order.update',
            'shipping.update',
        ];
        
        foreach ($webhooks as $event) {
            $api->register_webhook($event, $webhook_url);
        }
    }
}
