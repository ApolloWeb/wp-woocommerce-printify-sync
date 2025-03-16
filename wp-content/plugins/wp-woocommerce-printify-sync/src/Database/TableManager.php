<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database;

class TableManager
{
    public function createTables(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Fulfillment Tracking Table
        $sql_fulfillment = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_fulfillment_tracking (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            printify_id varchar(255) NOT NULL,
            status varchar(50) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            details longtext NOT NULL,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY printify_id (printify_id),
            KEY status (status)
        ) $charset_collate;";

        // Sync Log Table
        $sql_sync_log = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_sync_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type)
        ) $charset_collate;";

        // Product Sync Status Table
        $sql_product_sync = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_product_sync_status (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            printify_id varchar(255) NOT NULL,
            last_sync datetime NOT NULL,
            sync_status varchar(50) NOT NULL,
            sync_message text,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY printify_id (printify_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_fulfillment);
        dbDelta($sql_sync_log);
        dbDelta($sql_product_sync);
    }
}