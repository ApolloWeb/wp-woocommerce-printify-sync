<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Database;

/**
 * Database setup class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Database
 */
class Setup
{
    /**
     * Create database tables.
     *
     * @return void
     */
    public function createTables()
    {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create SQL for tables
        $sql = $this->getTicketTableSQL($charset_collate);
        $sql .= $this->getTicketRepliesTableSQL($charset_collate);
        $sql .= $this->getTicketAttachmentsTableSQL($charset_collate);
        
        // Add email queue table SQL
        $sql .= $this->getEmailQueueTableSQL($charset_collate);
        
        // Create activity log table
        $sql .= $this->getActivityLogTableSQL($charset_collate);
        
        // Execute SQL statements - we're not using dbDelta here as it's not always reliable
        $this->executeSQLStatements($sql);
    }
    
    /**
     * Get SQL for creating the ticket table.
     *
     * @param string $charset_collate Database charset.
     * @return string SQL statement.
     */
    private function getTicketTableSQL($charset_collate)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_tickets';
        
        return "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            order_id bigint(20),
            printify_order_id varchar(255),
            subject varchar(255) NOT NULL,
            content longtext NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'open',
            category varchar(50),
            urgency varchar(20) DEFAULT 'medium',
            is_refund_request tinyint(1) DEFAULT 0,
            is_reprint_request tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset_collate;";
    }
    
    /**
     * Get SQL for creating the ticket replies table.
     *
     * @param string $charset_collate Database charset.
     * @return string SQL statement.
     */
    private function getTicketRepliesTableSQL($charset_collate)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_ticket_replies';
        
        return "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) NOT NULL,
            user_id bigint(20),
            content longtext NOT NULL,
            is_from_customer tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id)
        ) $charset_collate;";
    }
    
    /**
     * Get SQL for creating the ticket attachments table.
     *
     * @param string $charset_collate Database charset.
     * @return string SQL statement.
     */
    private function getTicketAttachmentsTableSQL($charset_collate)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_ticket_attachments';
        
        return "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) NOT NULL,
            reply_id bigint(20),
            file_name varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            file_type varchar(100) NOT NULL,
            file_size bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id),
            KEY reply_id (reply_id)
        ) $charset_collate;";
    }
    
    /**
     * Get SQL for creating the email queue table.
     *
     * @param string $charset_collate Database charset.
     * @return string SQL statement.
     */
    private function getEmailQueueTableSQL($charset_collate)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_email_queue';
        
        return "CREATE TABLE IF NOT EXISTS $table_name (
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
    
    /**
     * Get SQL for creating the activity log table.
     *
     * @param string $charset_collate Database charset.
     * @return string SQL statement.
     */
    private function getActivityLogTableSQL($charset_collate)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_activity_log';
        
        return "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            message varchar(255) NOT NULL,
            data longtext,
            user_id bigint(20),
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
    }
    
    /**
     * Execute SQL statements.
     *
     * @param string $sql SQL statements to execute.
     * @return void
     */
    private function executeSQLStatements($sql)
    {
        global $wpdb;
        
        // Split SQL statements at semicolons
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            
            if (!empty($statement)) {
                $wpdb->query($statement);
            }
        }
    }
}
