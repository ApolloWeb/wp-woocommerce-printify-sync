<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateApiRequestsTable
{
    public function up(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_api_requests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            params text NOT NULL,
            response longtext NOT NULL,
            status_code int(11),
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            completed_at datetime,
            PRIMARY KEY  (id),
            KEY endpoint (endpoint),
            KEY created_at (created_at)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}