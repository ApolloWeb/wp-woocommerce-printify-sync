<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateApiLogTable
{
    public function up(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_api_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            api_type varchar(100) NOT NULL,
            method varchar(10) NOT NULL,
            url text NOT NULL,
            params text,
            response_code int(11),
            response_body longtext,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            PRIMARY KEY  (id),
            KEY api_type (api_type),
            KEY created_at (created_at)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}