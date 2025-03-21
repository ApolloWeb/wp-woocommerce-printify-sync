<?php
/**
 * Plugin activation handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;

/**
 * Handles plugin activation tasks.
 */
class Activation
{
    /**
     * Runs on plugin activation.
     *
     * @return void
     */
    public static function activate()
    {
        // Check for WooCommerce
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(WPWPS_PLUGIN_BASENAME);
            wp_die(
                esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync'),
                esc_html__('Plugin Activation Error', 'wp-woocommerce-printify-sync'),
                ['back_link' => true]
            );
        }

        // Create logs directory
        $logs_dir = WPWPS_PLUGIN_DIR . 'logs/';
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);

            // Create .htaccess to protect log files
            $htaccess = "# Disable directory browsing\nOptions -Indexes\n\n# Deny access to all files\n<FilesMatch \".*\">\nOrder Allow,Deny\nDeny from all\n</FilesMatch>";
            file_put_contents($logs_dir . '.htaccess', $htaccess);
        }

        // Create necessary database tables
        self::createDatabaseTables();

        // Set default plugin options
        self::setDefaultOptions();

        // Schedule cron jobs
        self::setupCronJobs();

        // Create required directories
        self::setupDirectories();

        // Log activation
        $logger = new Logger();
        $logger->info('Plugin activated');
    }

    /**
     * Create necessary database tables.
     *
     * @return void
     */
    private static function createDatabaseTables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Create the support_ticket table
        $table_name = $wpdb->prefix . 'wpwps_support_tickets';
        $sql = "CREATE TABLE $table_name (
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

        // Create the ticket_replies table
        $table_name_replies = $wpdb->prefix . 'wpwps_ticket_replies';
        $sql .= "CREATE TABLE $table_name_replies (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) NOT NULL,
            user_id bigint(20),
            content longtext NOT NULL,
            is_admin tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id)
        ) $charset_collate;";

        // Create the ticket_attachments table
        $table_name_attachments = $wpdb->prefix . 'wpwps_ticket_attachments';
        $sql .= "CREATE TABLE $table_name_attachments (
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

        // Create the email_queue table
        $table_name_email_queue = $wpdb->prefix . 'wpwps_email_queue';
        $sql .= "CREATE TABLE $table_name_email_queue (
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

        // Create the activity_log table
        $table_name_activity = $wpdb->prefix . 'wpwps_activity_log';
        $sql .= "CREATE TABLE $table_name_activity (
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

        // We don't need to use dbDelta here
        // Just execute the SQL queries
        $wpdb->query($sql);
    }

    /**
     * Set default plugin options.
     *
     * @return void
     */
    private static function setDefaultOptions()
    {
        // Printify API settings
        if (!get_option('wpwps_printify_api_endpoint')) {
            update_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1/');
        }

        // ChatGPT settings
        if (!get_option('wpwps_chatgpt_temperature')) {
            update_option('wpwps_chatgpt_temperature', 0.7);
        }

        if (!get_option('wpwps_chatgpt_monthly_budget')) {
            update_option('wpwps_chatgpt_monthly_budget', 10000);
        }

        // General settings
        if (!get_option('wpwps_log_level')) {
            update_option('wpwps_log_level', 'info');
        }
    }

    /**
     * Setup cron jobs.
     *
     * @return void
     */
    private static function setupCronJobs()
    {
        // Schedule stock sync if not already scheduled
        if (!wp_next_scheduled('wpwps_sync_products')) {
            wp_schedule_event(time(), 'twicedaily', 'wpwps_sync_products');
        }

        // Schedule email queue processing if not already scheduled
        if (!wp_next_scheduled('wpwps_process_email_queue')) {
            wp_schedule_event(time(), 'hourly', 'wpwps_process_email_queue');
        }
    }

    /**
     * Setup required directories.
     *
     * @return void
     */
    private static function setupDirectories()
    {
        // Create uploads directory for ticket attachments
        $upload_dir = wp_upload_dir();
        $attachments_dir = $upload_dir['basedir'] . '/wpwps-attachments';

        if (!file_exists($attachments_dir)) {
            wp_mkdir_p($attachments_dir);

            // Create .htaccess to protect sensitive files
            $htaccess = "# Disable directory browsing\nOptions -Indexes\n\n# Allow access only to image and document files\n<FilesMatch \".(jpg|jpeg|png|gif|pdf|doc|docx|xls|xlsx|txt)$\">\nOrder Allow,Deny\nAllow from all\n</FilesMatch>";
            file_put_contents($attachments_dir . '/.htaccess', $htaccess);
        }
    }
}
