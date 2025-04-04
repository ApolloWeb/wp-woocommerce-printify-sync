<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Database;

class Installer {
    public function install(): void 
    {
        $this->createTables();
        $this->setVersion();
    }

    private function createTables(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        // Support tickets table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_support_tickets (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subject varchar(255) NOT NULL,
            description longtext NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            user_id bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Email queue table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_email_queue (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            to_email varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            body longtext NOT NULL,
            headers text,
            attachments text,
            status varchar(50) NOT NULL DEFAULT 'pending',
            attempts int(11) NOT NULL DEFAULT 0,
            error text,
            created_at datetime NOT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    private function setVersion(): void
    {
        update_option('wpwps_db_version', WPWPS_VERSION);
    }
}
