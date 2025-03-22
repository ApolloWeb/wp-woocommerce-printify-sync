<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Database;

/**
 * Email Queue Table setup class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Email\Database
 */
class EmailQueueTable
{
    /**
     * Table name in the database.
     *
     * @var string
     */
    public static $table_name = 'wpwps_email_queue';

    /**
     * Creates the email queue table in the database.
     *
     * @return string SQL query to create the table.
     */
    public static function createTable()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();
        
        return "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            to_email varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            message longtext NOT NULL,
            headers text,
            attachments text,
            status varchar(20) DEFAULT 'pending',
            retry_count int(11) DEFAULT 0,
            error_message text,
            scheduled_time datetime NOT NULL,
            sent_time datetime,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charset_collate;";
    }
}
