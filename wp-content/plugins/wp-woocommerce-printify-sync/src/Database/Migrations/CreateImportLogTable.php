<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateImportLogTable
{
    public function up(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_import_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            printify_id varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            imported_at datetime NOT NULL,
            imported_by varchar(60) NOT NULL,
            details longtext NOT NULL,
            PRIMARY KEY  (id),
            KEY printify_id (printify_id),
            KEY status (status),
            KEY imported_at (imported_at)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}