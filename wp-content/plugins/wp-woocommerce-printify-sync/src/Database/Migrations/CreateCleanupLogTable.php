<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateCleanupLogTable
{
    public function up(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_cleanup_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            items text NOT NULL,
            status varchar(20) NOT NULL,
            error_message text,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}