<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateR2UploadsTable
{
    public function up(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_r2_uploads (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            file_key varchar(255) NOT NULL,
            etag varchar(64) NOT NULL,
            version_id varchar(64),
            size bigint(20) NOT NULL DEFAULT 0,
            mime_type varchar(100),
            uploaded_at datetime NOT NULL,
            uploaded_by varchar(60) NOT NULL,
            PRIMARY KEY  (id),
            KEY file_key (file_key),
            KEY uploaded_at (uploaded_at)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}