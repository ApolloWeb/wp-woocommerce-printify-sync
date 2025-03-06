<?php
/**
 * Email Processor
 *
 * Intelligent email processing system that handles incoming emails,
 * detects inquiry type, extracts customer information, and creates tickets.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Email
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Tickets\TicketManager;
use ApolloWeb\WPWooCommercePrintifySync\Customers\CustomerManager;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class EmailProcessor {
    /**
     * Singleton instance
     *
     * @var EmailProcessor
     */
    private static $instance = null;
    
    /**
     * POP3 client
     *
     * @var Pop3Client
     */
    private $pop3_client = null;
    
    /**
     * SMTP client
     *
     * @var SmtpClient
     */
    private $smtp_client = null;
    
    /**
     * Settings
     *
     * @var array
     */
    private $settings = array();
    
    /**
     * Natural Language Processor
     *
     * @var NLPAnalyzer
     */
    private $nlp = null;
    
    /**
     * Get singleton instance
     *
     * @return EmailProcessor
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
        $this->pop3_client = Pop3Client::get_instance();
        $this->smtp_client = SmtpClient::get_instance();
        $this->load_settings();
        $this->nlp = new NLPAnalyzer();
    }
    
    /**
     * Load settings
     */
    private function load_settings() {
        $plugin_settings = get_option('wpwprintifysync_settings', array());
        
        $this->settings = array(
            'enabled' => isset($plugin_settings['email_processor_enabled']) && $plugin_settings['email_processor_enabled'] === 'yes',
            'cron_interval' => isset($plugin_settings['email_processor_interval']) ? intval($plugin_settings['email_processor_interval']) : 5,
            'auto_create_tickets' => isset($plugin_settings['email_auto_tickets']) && $plugin_settings['email_auto_tickets'] === 'yes',
            'auto_assign_tickets' => isset($plugin_settings['email_auto_assign']) && $plugin_settings['email_auto_assign'] === 'yes',
            'detect_spam' => isset($plugin_settings['email_detect_spam']) && $plugin_settings['email_detect_spam'] === 'yes',
            'default_ticket_type' => isset($plugin_settings['email_default_ticket_type']) ? $plugin_settings['email_default_ticket_type'] : 'general',
            'customer_extraction' => isset($plugin_settings['email_customer_extraction']) ? $plugin_settings['email_customer_extraction'] : 'auto',
            'ticket_prefix' => isset($plugin_settings['email_ticket_prefix']) ? $plugin_settings['email_ticket_prefix'] : 'TKT',
            'send_confirmation' => isset($plugin_settings['email_send_confirmation']) && $plugin_settings['email_send_confirmation'] === 'yes',
            'confirmation_template' => isset($plugin_settings['email_confirmation_template']) ? $plugin_settings['email_confirmation_template'] : '',
            'max_emails_per_run' => isset($plugin_settings['email_max_per_run']) ? intval($plugin_settings['email_max_per_run']) : 20,
        );
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Add settings
        add_filter('wpwprintifysync_settings_sections', array($this, 'add_settings_section'));
        add_filter('wpwprintifysync_settings_fields', array($this, 'add_settings_fields'));
        
        if (!$this->settings['enabled']) {
            return;
        }
        
        // Register cron job
        add_action('wpwprintifysync_process_emails', array($this, 'process_emails'));
        
        if (!wp_next_scheduled('wpwprintifysync_process_emails')) {
            wp_schedule_event(time(), 'wpwprintifysync_email_interval', 'wpwprintifysync_process_emails');
        }
        
        add_filter('cron_schedules', array($this, 'add_cron_interval'));
        
        // Register admin hooks
        add_action('wp_ajax_wpwprintifysync_process_emails_now', array($this, 'ajax_process_emails'));
        
        // Register deactivation hook
        register_deactivation_hook(WPWPRINTIFYSYNC_PLUGIN_FILE, array($this, 'deactivation_cleanup'));
    }
    
    /**
     * Add custom cron interval
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_cron_interval($schedules) {
        $interval = max(1, $this->settings['cron_interval']) * 60; // Convert to seconds
        
        $schedules['wpwprintifysync_email_interval'] = array(
            'interval' => $interval,
            'display' => sprintf(__('Every %d minutes', 'wp-woocommerce-printify-sync'), $this->settings['cron_interval'])
        );
        
        return $schedules;
    }
    
    /**
     * Process emails via cron
     */
    public function process_emails() {
        if (!$this->settings['enabled']) {
            return;
        }
        
        Logger::get_instance()->info('Starting scheduled email processing');
        
        // First process outgoing queue
        $this->process_outgoing_queue();
        
        // Then process incoming emails
        $this->process_incoming_emails();
        
        Logger::get_instance()->info('Completed scheduled email processing');
    }
    
    /**
     * Process the outgoing email queue
     */
    private function process_outgoing_queue() {
        $this->smtp_client->process_email_queue();
    }
    
    /**
     * Process incoming emails and create tickets
     * 
     * @param int $max_emails Maximum emails to process
     * @return array Processing results
     */
    public function process_incoming_emails($max_emails = null) {
        if ($max_emails === null) {
            $max_emails = $this->settings['max_emails_per_run'];
        }
        
        $results = array(
            'processed' => 0,
            'tickets_created' => 0,
            'failed' => 0,
            'errors' => array(),
        );
        
        // Connect to the POP3 server
        if (!$this->pop3_client->connect()) {
            Logger::get_instance()->error('Failed to connect to POP3 server for email processing');
            $results['errors'][] = 'Failed to connect to POP3 server';
            return $results;
        }
        
        // Get list of messages
        $message_ids = $this->pop3_client->get_message_list($max_emails);
        
        if (empty($message_ids)) {
            Logger::get_instance()->info('No new emails to process');
            $this->pop3_client->disconnect();
            return $results;
        }
        
        Logger::get_instance()->info('Processing incoming emails', array(
            'count' => count($message_ids)
        ));
        
        // Process each message
        foreach ($message_ids as $message_id) {
            $email_content = $this->pop3_client->get_message($message_id);
            
            if (!$email_content) {
                $results['failed']++;
                $results['errors'][] = "Failed to retrieve message #$message_id";
                Logger::get_instance()->error('Failed to retrieve email', array(
                    'message_id' => $message_id
                ));
                continue;
            }
            
            try {
                // Parse the email
                $email_data = $this->parse_email($email_content);
                
                // Process the email data
                $ticket_id = $this->process_email_data($email_data);
                
                if ($ticket_id) {
                    $results['tickets_created']++;
                }
                
                $results['processed']++;
                
                // Mark for deletion if configured
                if ($this->pop3_client->is_delete_after_processing()) {
                    $this->pop3_client->mark_for_deletion($message_id);
                }
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
                Logger::get_instance()->error('Failed to process email', array(
                    'message_id' => $message_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ));
            }
        }
        
        // Commit deletions
        $this->pop3_client->commit_deletions();
        
        // Disconnect
        $this->pop3_client->disconnect();
        
        Logger::get_instance()->info('Email processing completed', $results);
        
        return $results;
    }
    
    /**
     * Parse raw email content
     *
     * @param string $email_content Raw email content
     * @return array Parsed email data
     */
    private function parse_email($email_content) {
        $helper = new Pop3Helper();
        $parsed_data = $helper->parse_email($email_content);
        
        // Add additional processing
        $parsed_data = $this->enhance_parsed_data($parsed_data);
        
        return $parsed_data;
    }
    
    /**
     * Enhance parsed email data with intelligent extraction
     *
     * @param array $parsed_data Basic parsed email data
     * @return array Enhanced data
     */
    private function enhance_parsed_data($parsed_data) {
        // Extract customer information
        $parsed_data['customer'] = $this->extract_customer_info($parsed_data);
        
        // Detect inquiry type
        $parsed_data['inquiry_type'] = $this->detect_inquiry_type($parsed_data);
        
        // Extract order ID if present
        $parsed_data['order_id'] = $this->extract_order_id($parsed_data);
        
        // Extract product information
        $parsed_data['product_info'] = $this->extract_product_info($parsed_data);
        
        // Detect urgency level
        $parsed_data['urgency'] = $this->detect_urgency($parsed_data);
        
        // Check for spam
        $parsed_data['is_spam'] = $this->settings['detect_spam'] ? $this->detect_spam($parsed_data) : false;
        
        // Generate suggested response if needed
        $parsed_data['suggested_response'] = $this->generate_suggested_response($parsed_data);
        
        return $parsed_data;
    }
    
    /**
     * Extract customer information from email
     *
     * @param array $email_data Parsed email data
     * @return array Customer information
     */
    private function extract_customer_info($email_data) {
        $customer_info = array(
            'email' => '',
            'name' => '',
            'customer_id' => 0,
            'exists' => false,
            'order_count' => 0,
            'total_spent' => 0,
            'last_order_date' => '',
        );
        
        // Extract email address
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $email_data['from'], $matches)) {
            $customer_info['email'] = $matches[1];
        }
        
        // Extract name from "From" header
        if (preg_match('/(.*)<.*>/', $email_data['from'], $matches)) {
            $customer_info['name'] = trim($matches[1]);
        }
        
        // If we have an email, try to find the customer
        if (!empty($customer_info['email'])) {
            $customer_manager = new CustomerManager();
            $customer = $customer_manager->get_customer_by_email($customer_info['email']);
            
            if ($customer) {
                $customer_info['customer_id'] = $customer->get_id();
                $customer_info['exists'] = true;
                $customer_info['name'] = $customer->get_first_name() . ' ' . $customer->get_last_name();
                
                // Get order stats
                $order_count = $customer_manager->get_customer_order_count($customer_info['customer_id']);
                $customer_info['order_count'] = $order_count;
                
                $total_spent = $customer_manager->get_customer_total_spent($customer_info['customer_id']);
                $customer_info['total_spent'] = $total_spent;
                
                $last_order = $customer_manager->get_customer_last_order($customer_info['customer_id']);
                if ($last_order) {
                    $customer_info['last_order_date'] = $last_order->get_date_created()->date('Y-m-d H:i:s');
                }
            }
        }
        
        return $customer_info;
    }
    
    /**
     * Detect inquiry type from email content
     *
     * @param array $email_data Parsed email data
     * @return string Inquiry type
     */
    private function detect_inquiry_type($email_data) {
        $content = $email_data['text_body'] . ' ' . $email_data['html_body'];
        $subject = $email_data['subject'];
        
        // Default type
        $inquiry_type = $this->settings['default_ticket_type'];
        
        // Check for common patterns in subject and content
        $patterns = array(
            'order_status' => array(
                'where is my order',
                'order status',
                'shipping update',
                'delivery status',
                'when will my order',
                'tracking number',
                'my package',
                'where is my package',
                'order delayed',
                'order shipped',
            ),
            'return_refund' => array(
                'return',
                'refund',
                'money back',
                'send back',
                'cancel my order',
                'damaged item',
                'wrong item',
                'not what I ordered',
            ),
            'product_question' => array(
                'product question',
                'product info',
                'product dimensions',
                'material question',
                'sizing',
                'size chart',
                'color options',
                'customization',
                'design question',
            ),
            'technical_issue' => array(
                'website error',
                'checkout problem',
                'payment failed',
                'can\'t complete',
                'technical issue',
                'website not working',
                'app not working',
                'error message',
                'website crash',
                '404',
                'broken link',
            ),
            'account_issue' => array(
                'account problem',
                'can\'t login',
                'password reset