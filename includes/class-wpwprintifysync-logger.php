<?php
/**
 * Logger Class
 *
 * @package WP_WooCommerce_Printify_Sync
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logger for plugin events
 */
class WPWPRINTIFYSYNC_Logger {
    /**
     * Singleton instance
     *
     * @var WPWPRINTIFYSYNC_Logger
     */
    private static $instance = null;
    
    /**
     * Log levels
     *
     * @var array
     */
    private $levels = array(
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7,
    );
    
    /**
     * Database table name
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Current timestamp
     *
     * @var string
     */
    private $timestamp;
    
    /**
     * Log to file
     *
     * @var bool
     */
    private $log_to_file;
    
    /**
     * Log to database
     *
     * @var bool
     */
    private $log_to_db;
    
    /**
     * Get singleton instance
     *
     * @return WPWPRINTIFYSYNC_Logger
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
        global $wpdb;
        $this->timestamp = current_time('mysql');
        $this->table_name = $wpdb->prefix . 'wpwprintifysync_logs';
        
        $this->log_to_file = get_option('wpwprintifysync_log_to_file', true);
        $this->log_to_db = get_option('wpwprintifysync_log_to_db', true);
    }
    
    /**
     * Add log entry
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function log($level, $message, $context = array()) {
        if (!in_array($level, array_keys($this->levels))) {
            $level = 'info';
        }
        
        // Only log if enabled
        $min_level = get_option('wpwprintifysync_min_log_level', 'error');
        if ($this->levels[$level] > $this->levels[$min_level]) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => $this->timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context
        );
        
        // Log to database
        if ($this->log_to_db) {
            $this->log_to_database($log_entry);
        }
        
        // Log to file
        if ($this->log_to_file) {
            $this->log_to_file($log_entry);
        }
    }
    
    /**
     * Log to database
     *
     * @param array $entry Log entry data
     */
    private function log_to_database($entry) {
        global $wpdb;
        
        // Make sure the table exists
        if ($this->check_create_table()) {
            $wpdb->insert(
                $this->table_name,
                array(
                    'level' => $entry['level'],
                    'message' => $entry['message'],
                    'context' => is_array($entry['context']) ? json_encode($entry['context']) :