<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support\Database;

class Installer {
    public function install(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = [
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_email_queue (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                to_email varchar(255) NOT NULL,
                subject varchar(255) NOT NULL,
                body longtext NOT NULL,
                attachments longtext,
                status varchar(20) NOT NULL DEFAULT 'pending',
                priority tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                scheduled_for datetime DEFAULT NULL,
                attempts tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY  (id),
                KEY status (status),
                KEY priority (priority)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_ticket_threads (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                ticket_id bigint(20) NOT NULL,
                author_id bigint(20) NOT NULL,
                content longtext NOT NULL,
                attachments longtext,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY ticket_id (ticket_id)
            ) $charset_collate;"
        ];
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }
}
