<?php
namespace ApolloWeb\WPWooCommercePrintifySync\DB;

class Installer {
    public function install(): void {
        $this->createTables();
    }

    private function createTables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpps_sync_progress (
            id bigint(20) unsigned NOT NULL auto_increment,
            type varchar(50) NOT NULL,
            total_items int unsigned NOT NULL default 0,
            processed_items int unsigned NOT NULL default 0,
            failed_items int unsigned NOT NULL default 0,
            status varchar(20) NOT NULL default 'pending',
            started_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            last_error text DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
