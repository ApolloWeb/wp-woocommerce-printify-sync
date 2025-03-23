<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Installer {
    public static function install(): void {
        self::createTables();
        self::addCronSchedules();
        self::setDefaultOptions();
    }

    private static function createTables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Email Queue Table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_email_queue (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            to_email varchar(255) NOT NULL,
            subject text NOT NULL,
            message longtext NOT NULL,
            headers text,
            attachments text,
            status varchar(20) DEFAULT 'pending',
            attempts smallint(5) DEFAULT 0,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            scheduled_for datetime DEFAULT CURRENT_TIMESTAMP,
            sent_at datetime,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // API Logs Table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_api_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            request text,
            response text,
            status int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Webhook Events Table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_webhook_events (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            payload longtext NOT NULL,
            processed tinyint(1) DEFAULT 0,
            processed_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY processed (processed)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private static function setDefaultOptions(): void {
        // Set up webhook URL
        $webhook_url = add_query_arg('action', 'wpwps_webhook', WC()->api_request_url('wpwps_webhook'));
        update_option('wpwps_webhook_url', $webhook_url);
    }
}
