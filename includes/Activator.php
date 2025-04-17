<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

/**
 * Plugin Activator
 */
class Activator {
    /**
     * Activate the plugin
     */
    public function activate() {
        $this->create_tables();
        $this->create_default_options();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create webhooks table
        $table_name = $wpdb->prefix . 'wpwps_webhooks';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            printify_id varchar(255) NOT NULL,
            topic varchar(100) NOT NULL,
            url varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY printify_id (printify_id),
            KEY topic (topic),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create default options
     */
    private function create_default_options() {
        // Default options
        $default_options = [
            'printify_api_endpoint' => WPWPS_PRINTIFY_API_URL,
            'enable_sync' => 1,
            'sync_interval' => 60,
            'chatgpt_model' => 'gpt-3.5-turbo',
            'chatgpt_monthly_cap' => 10,
            'chatgpt_token_limit' => 1000,
            'chatgpt_temperature' => 0.7,
        ];
        
        // Only set options if they don't exist
        $options = get_option('wpwps_options', []);
        $updated_options = array_merge($default_options, $options);
        
        update_option('wpwps_options', $updated_options);
    }
}
