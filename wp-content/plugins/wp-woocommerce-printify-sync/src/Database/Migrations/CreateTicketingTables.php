<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateTicketingTables
{
    public function up(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        // Tickets table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_tickets (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            subject varchar(255) NOT NULL,
            customer_email varchar(100) NOT NULL,
            status varchar(20) NOT NULL,
            priority varchar(20) NOT NULL,
            order_id bigint(20),
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            updated_at datetime,
            updated_by varchar(60),
            PRIMARY KEY  (id),
            KEY status (status),
            KEY priority (priority),
            KEY customer_email (customer_email),
            KEY order_id (order_id)
        ) $charsetCollate;";

        // Messages table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_ticket_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) NOT NULL,
            message longtext NOT NULL,
            is_customer tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id)
        ) $charsetCollate;";

        // Attachments table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_ticket_attachments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            message_id bigint(20) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path text NOT NULL,
            file_type varchar(100) NOT NULL,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            PRIMARY KEY  (id),
            KEY message_id (message_id)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}