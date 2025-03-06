<?php
/**
 * SMTP Client
 *
 * Handles SMTP email sending with queue functionality.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Email
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SmtpClient {
    /**
     * Singleton instance
     *
     * @var SmtpClient
     */
    private static $instance = null;
    
    /**
     * SMTP settings
     *
     * @var array
     */
    private $settings = array();
    
    /**
     * Get singleton instance
     *
     * @return SmtpClient
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_settings();
    }
    
    /**
     * Load settings
     */
    private function load_settings() {
        $plugin_settings = get_option('wpwprintifysync_settings', array());
        
        // We'll use the same server settings as POP3 but with SMTP port and protocol
        $this->settings = array(
            'host' => isset($plugin_settings['smtp_host']) ? $plugin_settings['smtp_host'] : 
                     (isset($plugin_settings['pop3_host']) ? $plugin_settings['pop3_host'] : ''),
            'port' => isset($plugin_settings['smtp_port']) ? intval($plugin_settings['smtp_port']) : 587,
            'username' => isset($plugin_settings['smtp_username']) ? $plugin_settings['smtp_username'] : 
                         (isset($plugin_settings['pop3_username']) ? $plugin_settings['pop3_username'] : ''),
            'password' => isset($plugin_settings['smtp_password']) ? $plugin_settings['smtp_password'] : 
                         (isset($plugin_settings['pop3_password']) ? $plugin_settings['pop3_password'] : ''),
            'from_email' => isset($plugin_settings['smtp_from_email']) ? $plugin_settings['smtp_from_email'] : '',
            'from_name' => isset($plugin_settings['smtp_from_name']) ? $plugin_settings['smtp_from_name'] : '',
            'encryption' => isset($plugin_settings['smtp_encryption']) ? $plugin_settings['smtp_encryption'] : 'tls',
            'enabled' => isset($plugin_settings['smtp_enabled']) && $plugin_settings['smtp_enabled'] === 'yes',
            'queue_enabled' => isset($plugin_settings['smtp_queue_enabled']) && $plugin_settings['smtp_queue_enabled'] === 'yes',
            'queue_batch_size' => isset($plugin_settings['smtp_queue_batch_size']) ? intval($plugin_settings['smtp_queue_batch_size']) : 10,
            'queue_interval' => isset($plugin_settings['smtp_queue_interval']) ? intval($plugin_settings['smtp_queue_interval']) : 5,
            'retry_limit' => isset($plugin_settings['smtp_retry_limit']) ? intval($plugin_settings['smtp_retry_limit']) : 3,
        );
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Register settings
        add_filter('wpwprintifysync_settings_sections', array($this, 'add_settings_section'));
        add_filter('wpwprintifysync_settings_fields', array($this, 'add_settings_fields'));
        
        // Only proceed if SMTP is enabled
        if (!$this->settings['enabled']) {
            return;
        }
        
        // Replace WordPress email function if enabled
        add_action('phpmailer_init', array($this, 'configure_phpmailer'), 10, 1);
        
        // Set up email queue processing
        if ($this->settings['queue_enabled']) {
            // Register custom database tables
            add_action('plugins_loaded', array($this, 'register_tables'));
            
            // Set up cron task for processing queue
            add_action('wpwprintifysync_smtp_process_queue', array($this, 'process_email_queue'));
            
            if (!wp_next_scheduled('wpwprintifysync_smtp_process_queue')) {
                wp_schedule_event(time(), 'wpwprintifysync_smtp_interval', 'wpwprintifysync_smtp_process_queue');
            }
            
            add_filter('cron_schedules', array($this, 'add_cron_interval'));
            
            // Register hooks for adding to queue
            add_filter('wp_mail', array($this, 'intercept_wp_mail'), 10, 1);
        }
        
        // Register cleanup task
        register_deactivation_hook(WPWPRINTIFYSYNC_PLUGIN_FILE, array($this, 'deactivation_cleanup'));
    }
    
    /**
     * Register custom database tables
     */
    public function register_tables() {
        global $wpdb;
        
        $wpdb->wpwp_email_queue = $wpdb->prefix . 'wpwp_email_queue';
        
        // Create table if it doesn't exist
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->wpwp_email_queue}'") !== $wpdb->wpwp_email_queue) {
            $this->create_tables();
        }
    }
    
    /**
     * Create email queue table
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$wpdb->wpwp_email_queue} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            to_email text NOT NULL,
            subject text NOT NULL,
            message longtext NOT NULL,
            headers text,
            attachments text,
            status varchar(20) NOT NULL DEFAULT 'queued',
            date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            date_scheduled datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            date_processed datetime DEFAULT NULL,
            retry_count int(11) NOT NULL DEFAULT 0,
            error_message text,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY date_scheduled (date_scheduled)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add custom cron interval
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_cron_interval($schedules) {
        $interval = max(1, $this->settings['queue_interval']) * 60; // Convert to seconds
        
        $schedules['wpwprintifysync_smtp_interval'] = array(
            'interval' => $interval,
            'display' => sprintf(__('Every %d minutes', 'wp-woocommerce-printify-sync'), $this->settings['queue_interval'])
        );
        
        return $schedules;
    }
    
    /**
     * Add settings section
     *
     * @param array $sections Existing sections
     * @return array Modified sections
     */
    public function add_settings_section($sections) {
        if (!isset($sections['email'])) {
            $sections['email'] = array();
        }
        
        $sections['email']['smtp'] = array(
            'title' => __('SMTP Settings', 'wp-woocommerce-printify-sync'),
            'description' => __('Configure SMTP email sending with queue functionality.', 'wp-woocommerce-printify-sync')
        );
        
        return $sections;
    }
    
    /**
     * Add settings fields
     *
     * @param array $fields Existing fields
     * @return array Modified fields
     */
    public function add_settings_fields($fields) {
        if (!isset($fields['email'])) {
            $fields['email'] = array();
        }
        
        $fields['email']['smtp'] = array(
            array(
                'id' => 'smtp_enabled',
                'name' => __('Enable SMTP', 'wp-woocommerce-printify-sync'),
                'type' => 'checkbox',
                'default' => 'no',
                'description' => __('Enable SMTP for sending emails.', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_host',
                'name' => __('SMTP Host', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'default' => '',
                'placeholder' => 'smtp.example.com',
                'description' => __('Your SMTP server hostname.', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_port',
                'name' => __('SMTP Port', 'wp-woocommerce-printify-sync'),
                'type' => 'number',
                'default' => '587',
                'description' => __('SMTP port (usually 25, 465, or 587).', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_encryption',
                'name' => __('Encryption', 'wp-woocommerce-printify-sync'),
                'type' => 'select',
                'options' => array(
                    '' => __('None', 'wp-woocommerce-printify-sync'),
                    'ssl' => __('SSL', 'wp-woocommerce-printify-sync'),
                    'tls' => __('TLS', 'wp-woocommerce-printify-sync')
                ),
                'default' => 'tls',
                'description' => __('Type of encryption to use.', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_username',
                'name' => __('Username', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'default' => '',
                'description' => __('Your SMTP account username.', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_password',
                'name' => __('Password', 'wp-woocommerce-printify-sync'),
                'type' => 'password',
                'default' => '',
                'description' => __('Your SMTP account password.', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_from_email',
                'name' => __('From Email', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'default' => get_option('admin_email'),
                'description' => __('The email address to send from.', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_from_name',
                'name' => __('From Name', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'default' => get_option('blogname'),
                'description' => __('The name to send emails as.', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_queue_enabled',
                'name' => __('Enable Email Queue', 'wp-woocommerce-printify-sync'),
                'type' => 'checkbox',
                'default' => 'yes',
                'description' => __('Queue emails for scheduled sending instead of sending immediately.', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_queue_batch_size',
                'name' => __('Queue Batch Size', 'wp-woocommerce-printify-sync'),
                'type' => 'number',
                'min' => 1,
                'max' => 50,
                'default' => 10,
                'description' => __('Number of emails to send in each batch.', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_queue_interval',
                'name' => __('Queue Processing Interval (minutes)', 'wp-woocommerce-printify-sync'),
                'type' => 'number',
                'min' => 1,
                'max' => 60,
                'default' => 5,
                'description' => __('How often to process the email queue (in minutes).', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_retry_limit',
                'name' => __('Retry Limit', 'wp-woocommerce-printify-sync'),
                'type' => 'number',
                'min' => 0,
                'max' => 10,
                'default' => 3,
                'description' => __('Number of times to retry sending failed emails.', 'wp-woocommerce-printify-sync')
            ),
            array(
                'id' => 'smtp_actions',
                'name' => __('SMTP Actions', 'wp-woocommerce-printify-sync'),
                'type' => 'html',
                'html' => '<button type="button" id="test-smtp-connection" class="button button-secondary">' . __('Send Test Email', 'wp-woocommerce-printify-sync') . '</button> <button type="button" id="process-email-queue" class="button button-secondary">' . __('Process Queue Now', 'wp-woocommerce-printify-sync') . '</button><div id="smtp-status-message"></div>',
                'description' => __('Test your SMTP configuration or manually process the email queue.', 'wp-woocommerce-printify-sync')
            )
        );
        
        return $fields;
    }
    
    /**
     * Configure PHPMailer to use SMTP
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance
     */
    public function configure_phpmailer($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host = $this->settings['host'];
        $phpmailer->Port = $this->settings['port'];
        
        if (!empty($this->settings['encryption'])) {
            $phpmailer->SMTPSecure = $this->settings['encryption'];
        }
        
        if (!empty($this->settings['username']) && !empty($this->settings['password'])) {
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $this->settings['username'];
            $phpmailer->Password = $this->settings['password'];
        }
        
        if (!empty($this->settings['from_email'])) {
            $phpmailer->From = $this->settings['from_email'];
        }
        
        if (!empty($this->settings['from_name'])) {
            $phpmailer->FromName = $this->settings['from_