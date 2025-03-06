<?php
/**
 * POP3 Client
 *
 * Core POP3 connection handling functionality.
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

class Pop3Client {
    /**
     * Singleton instance
     *
     * @var Pop3Client
     */
    private static $instance = null;
    
    /**
     * POP3 connection
     *
     * @var resource
     */
    private $connection = null;
    
    /**
     * Connection settings
     *
     * @var array
     */
    private $settings = array();
    
    /**
     * Messages marked for deletion
     *
     * @var array
     */
    private $deletion_queue = array();
    
    /**
     * Get singleton instance
     *
     * @return Pop3Client
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
        $this->settings = Pop3Settings::get_instance()->get_settings();
    }
    
    /**
     * Initialize
     */
    public function init() {
        if (!$this->settings['enabled']) {
            return;
        }
        
        // Register cron task if auto-processing is enabled
        if ($this->settings['auto_process']) {
            add_action('wpwprintifysync_pop3_check', array($this, 'cron_process_emails'));
            
            if (!wp_next_scheduled('wpwprintifysync_pop3_check')) {
                wp_schedule_event(time(), 'wpwprintifysync_pop3_interval', 'wpwprintifysync_pop3_check');
            }
            
            add_filter('cron_schedules', array($this, 'add_cron_interval'));
        }
        
        // Deactivation cleanup
        register_deactivation_hook(WPWPRINTIFYSYNC_PLUGIN_FILE, array($this, 'deactivation_cleanup'));
    }
    
    /**
     * Add custom cron interval
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_cron_interval($schedules) {
        $interval = max(5, $this->settings['auto_process_interval']) * 60; // Convert to seconds
        
        $schedules['wpwprintifysync_pop3_interval'] = array(
            'interval' => $interval,
            'display' => sprintf(__('Every %d minutes', 'wp-woocommerce-printify-sync'), $this->settings['auto_process_interval'])
        );
        
        return $schedules;
    }
    
    /**
     * Process emails via cron
     */
    public function cron_process_emails() {
        Logger::get_instance()->info('Starting scheduled POP3 email processing');
        $processor = new Pop3Processor();
        $processor->process_emails($this->settings['max_emails_per_batch']);
    }
    
    /**
     * Deactivation cleanup
     */
    public function deactivation_cleanup() {
        wp_clear_scheduled_hook('wpwprintifysync_pop3_check');
    }
    
    /**
     * Connect to POP3 server
     *
     * @return bool Success status
     */
    public function connect() {
        if (empty($this->settings['host']) || empty($this->settings['username']) || empty($this->settings['password'])) {
            Logger::get_instance()->error('POP3 connection failed: Invalid settings');
            return false;
        }
        
        // Close any existing connection
        $this->disconnect();
        
        $host = ($this->settings['ssl'] ? 'ssl://' : '') . $this->settings['host'];
        $port = $this->settings['port'];
        $timeout = 15;
        
        try {
            // Open connection
            $this->connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
            
            if (!$this->connection) {
                Logger::get_instance()->error('POP3 connection failed', array(
                    'error' => $errstr,
                    'code' => $errno
                ));
                return false;
            }
            
            // Set stream timeout
            stream_set_timeout($this->connection, $timeout);
            
            // Read the welcome message
            $welcome = fgets($this->connection, 1024);
            
            if (substr($welcome, 0, 3) !== '+OK') {
                $this->disconnect();
                Logger::get_instance()->error('POP3 server did not send welcome message', array(
                    'response' => $welcome
                ));
                return false;
            }
            
            // Authenticate
            fputs($this->connection, "USER {$this->settings['username']}\r\n");
            $response = fgets($this->connection, 1024);
            
            if (substr($response, 0, 3) !== '+OK') {
                $this->disconnect();
                Logger::get_instance()->error('POP3 username rejected', array(
                    'response' => $response
                ));
                return false;
            }
            
            fputs($this->connection, "PASS {$this->settings['password']}\r\n");
            $response = fgets($this->connection, 1024);
            
            if (substr($response, 0, 3) !== '+OK') {
                $this->disconnect();
                Logger::get_instance()->error('POP3 password rejected', array(
                    'response' => $response
                ));
                return false;
            }
            
            Logger::get_instance()->info('POP3 connection established', array(
                'host' => $this->settings['host']
            ));
            
            return true;
            
        } catch (\Exception $e) {
            $this->disconnect();
            Logger::get_instance()->error('POP3 connection exception', array(
                'error' => $e->getMessage()
            ));
            return false;
        }
    }
    
    /**
     * Disconnect from POP3 server
     */
    public function disconnect() {
        if ($this->connection) {
            // Send QUIT
            fputs($this->connection, "QUIT\r\n");
            fclose($this->connection);
            $this->connection = null;
            $this->deletion_queue = array();
            
            Logger::get_instance()->debug('POP3 connection closed');
        }
    }
    
    /**
     * Get message count
     *
     * @return int|false Message count or false on failure
     */
    public function get_message_count() {
        if (!$this->connection && !$this->connect()) {
            return false;
        }
        
        fputs($this->connection, "STAT\r\n");
        $response = fgets($this->connection, 1024);
        
        if (substr($response, 0, 3) !== '+OK') {
            return false;
        }
        
        $parts = explode(' ', $response);
        if (count($parts) < 3) {
            return false;
        }
        
        return (int)$parts[1];
    }
    
    /**
     * Get message by ID
     *
     * @param int $message_id Message ID
     * @return string|false Message content or false on failure
     */
    public function get_message($message_id) {
        if (!$this->connection && !$this->connect()) {
            return false;
        }
        
        fputs($this->connection, "RETR $message_id\r\n");
        $response = fgets($this->connection, 1024);
        
        if (substr($response, 0, 3) !== '+OK') {
            Logger::get_instance()->error('Failed to retrieve message', array(
                'message_id' => $message_id,
                'response' => $response
            ));
            return false;
        }
        
        $message = '';
        while (!feof($this->connection)) {
            $line = fgets($this->connection, 1024);
            if ($line === ".\r\n") {
                break;
            }
            $message .= $line;
        }
        
        return $message;
    }
    
    /**
     * Mark message for deletion
     *
     * @param int $message_id Message ID
     * @return bool Success status
     */
    public function mark_for_deletion($message_id) {
        if (!$this->connection && !$this->connect()) {
            return false;
        }
        
        fputs($this->connection, "DELE $message_id\r\n");
        $response = fgets($this->connection, 1024);
        
        if (substr($response, 0, 3) !== '+OK') {
            Logger::get_instance()->error('Failed to mark message for deletion', array(
                'message_id' => $message_id,
                'response' => $response
            ));
            return false;
        }
        
        $this->deletion_queue[] = $message_id;
        return true;
    }
    
    /**
     * Commit deletions (via QUIT command)
     *
     * @return bool Success status
     */
    public function commit_deletions() {
        if (!$this->connection) {
            return false;
        }
        
        if (empty($this->deletion_queue)) {
            return true;
        }
        
        $count = count($this->deletion_queue);
        
        Logger::get_instance()->info('Committing message deletions', array(
            'count' => $count,
            'messages' => $this->deletion_queue
        ));
        
        // Disconnect will send QUIT which commits the deletions
        $this->disconnect();
        
        return true;
    }
    
    /**
     * Get list of message IDs
     *
     * @param int $max Maximum number of messages to list
     * @return array Message IDs
     */
    public function get_message_list($max = 0) {
        if (!$this->connection && !$this->connect()) {
            return array();
        }
        
        fputs($this->connection, "LIST\r\n");
        $response = fgets($this->connection, 1024);
        
        if (substr($response, 0, 3) !== '+OK') {
            Logger::get_instance()->error('Failed to list messages', array(
                'response' => $response
            ));
            return array();
        }
        
        $messages = array();
        while (!feof($this->connection)) {
            $line = fgets($this->connection, 1024);
            if ($line === ".\r\n") {
                break;
            }
            
            $parts = explode(' ', trim($line));
            if (count($parts) >= 2) {
                $id = (int)$parts[0];
                if ($id > 0) {
                    $messages[] = $id;
                    
                    if ($max > 0 && count($messages) >= $max) {
                        break;
                    }
                }
            }
        }
        
        return $messages;
    }
}