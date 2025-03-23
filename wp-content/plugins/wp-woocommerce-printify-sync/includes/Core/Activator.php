<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Activator {
    public function activate(): void {
        global $wpdb;
        
        // Create email queue table
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wpwps_email_queue';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            to_email varchar(255) NOT NULL,
            subject text NOT NULL,
            message longtext NOT NULL,
            headers text,
            attachments text,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            attempts smallint(5) DEFAULT 0 NOT NULL,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            scheduled_for datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            sent_at datetime,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at),
            KEY scheduled_for (scheduled_for)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add default options
        add_option('wpwps_version', WPPS_VERSION);
        add_option('wpwps_api_daily_limit', 5000);
        add_option('wpwps_sync_interval', 21600); // 6 hours
    }
}
