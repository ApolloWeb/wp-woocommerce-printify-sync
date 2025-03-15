<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateImageTrackingLogTable
{
    public function up(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_image_tracking_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            attachment_id bigint(20) NOT NULL,
            source_url text NOT NULL,
            status varchar(20) NOT NULL,
            error_message text,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            PRIMARY KEY  (id),
            KEY attachment_id (attachment_id),
            KEY status (status)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}