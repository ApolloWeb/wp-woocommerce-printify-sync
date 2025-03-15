<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateQueueTable
{
    public function up(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_queue (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            task varchar(50) NOT NULL,
            data longtext NOT NULL,
            status varchar(20) NOT NULL,
            error text,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            completed_at datetime,
            completed_by varchar(60),
            failed_at datetime,
            attempts int(11) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY task (task)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}