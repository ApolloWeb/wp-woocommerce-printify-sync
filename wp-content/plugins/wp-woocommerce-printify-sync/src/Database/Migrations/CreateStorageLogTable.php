<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateStorageLogTable
{
    public function up(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_storage_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            file_path text NOT NULL,
            storage_provider varchar(50) NOT NULL,
            metadata text,
            status varchar(20) DEFAULT 'pending',
            error_message text,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY storage_provider (storage_provider)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}