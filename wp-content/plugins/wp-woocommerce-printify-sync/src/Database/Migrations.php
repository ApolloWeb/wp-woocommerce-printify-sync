<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database;

class Migrations
{
    public function up(): void
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Logs table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            component varchar(50) NOT NULL,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            PRIMARY KEY  (id),
            KEY level (level),
            KEY component (component),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql);

        // Exchange rates table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_exchange_rates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            currency_from varchar(3) NOT NULL,
            currency_to varchar(3) NOT NULL,
            rate decimal(10,6) NOT NULL,
            last_updated datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY currency_pair (currency_from,currency_to),
            KEY last_updated (last_updated)
        ) $charset_collate;";
        dbDelta($sql);

        // Product sync status table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_product_sync (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            printify_id varchar(60) NOT NULL,
            wc_product_id bigint(20) NOT NULL,
            last_synced datetime NOT NULL,
            sync_status varchar(20) NOT NULL,
            sync_message text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY printify_id (printify_id),
            KEY wc_product_id (wc_product_id),
            KEY sync_status (sync_status)
        ) $charset_collate;";
        dbDelta($sql);

        // Shipping profiles table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_shipping_profiles (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            printify_profile_id varchar(60) NOT NULL,
            wc_zone_id bigint(20) NOT NULL,
            profile_data longtext NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            last_synced datetime NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY printify_profile_id (printify_profile_id),
            KEY wc_zone_id (wc_zone_id),
            KEY is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql);

        // Support tickets table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_tickets (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ticket_number varchar(20) NOT NULL,
            customer_email varchar(100) NOT NULL,
            subject varchar(255) NOT NULL,
            content longtext NOT NULL,
            status varchar(20) NOT NULL,
            priority varchar(20) NOT NULL,
            order_id bigint(20),
            assigned_to bigint(20),
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY ticket_number (ticket_number),
            KEY customer_email (customer_email),
            KEY status (status),
            KEY order_id (order_id),
            KEY assigned_to (assigned_to)
        ) $charset_collate;";
        dbDelta($sql);

        // Ticket messages table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_ticket_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) NOT NULL,
            message_type varchar(20) NOT NULL,
            content longtext NOT NULL,
            attachments text,
            created_by varchar(60) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id),
            KEY message_type (message_type)
        ) $charset_collate;";
        dbDelta($sql);
    }

    public function down(): void
    {
        global $wpdb;

        $tables = [
            'wpwps_logs',
            'wpwps_exchange_rates',
            'wpwps_product_sync',
            'wpwps_shipping_profiles',
            'wpwps_tickets',
            'wpwps_ticket_messages'
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }
    }
}