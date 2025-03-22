<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class Database {
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    public function install(): void {
        $this->createTables();
    }
    
    private function createTables(): void {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = [
            "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}wpps_sync_log (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                type varchar(50) NOT NULL,
                message text NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}wpps_queue (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                job varchar(50) NOT NULL,
                payload longtext NOT NULL,
                attempts int(11) NOT NULL DEFAULT '0',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;"
        ];

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }
}
