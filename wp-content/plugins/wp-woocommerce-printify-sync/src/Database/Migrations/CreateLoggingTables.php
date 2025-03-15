<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateLoggingTables
{
    public function up(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $tables = [
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_image_tracking_log (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                attachment_id bigint(20) NOT NULL,
                image_url text NOT NULL,
                image_hash varchar(32) NOT NULL,
                created_at datetime NOT NULL,
                created_by varchar(60) NOT NULL,
                PRIMARY KEY  (id),
                KEY attachment_id (attachment_id)
            ) $charsetCollate;",

            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_api_retry_log (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                endpoint varchar(255) NOT NULL,
                attempt int(11) NOT NULL,
                backoff_time int(11) NOT NULL,
                error_code int(11) NOT NULL,
                error_message text NOT NULL,
                created_at datetime NOT NULL,
                created_by varchar(60) NOT NULL,
                PRIMARY KEY  (id),
                KEY endpoint (endpoint)
            ) $charsetCollate;",

            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_rate_limit_log (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                endpoint varchar(255) NOT NULL,
                wait_time int(11) NOT NULL,
                created_at datetime NOT NULL,
                created_by varchar(60) NOT NULL,
                PRIMARY KEY  (id),
                KEY endpoint (endpoint)
            ) $charsetCollate;",

            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_api_failure_log (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                endpoint varchar(255) NOT NULL,
                attempts int(11) NOT NULL,
                final_error text NOT NULL,
                created_at datetime NOT NULL,
                created_by varchar(60) NOT NULL,
                PRIMARY KEY  (id),
                KEY endpoint (endpoint)
            ) $charsetCollate;",

            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_error_log (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                message text NOT NULL,
                context longtext NOT NULL,
                created_at datetime NOT NULL,
                created_by varchar(60) NOT NULL,
                PRIMARY KEY  (id)
            ) $charsetCollate;"
        ];

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }
}