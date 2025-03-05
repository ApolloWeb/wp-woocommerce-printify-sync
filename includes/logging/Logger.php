<?php
/**
 * Logger Class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Logging
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Logging;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Logger {
    /**
     * Singleton instance
     *
     * @var Logger
     */
    private static $instance = null;
    
    /**
     * Log levels
     *
     * @var array
     */
    private $levels = [
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7,
    ];
    
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
     * @return Logger
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
    }
    
    /**
     * Initialize the logger
     */
    public function init() {
        $this->log_to_file = get_option('wpwprintifysync_log_to_file', true);
        $this->log_to_db = get_option('wpwprintifysync_log_to_db', true);
        
        // Create log table if it doesn't exist
        if ($this->log_to_db) {
            $this->create_logs_table();
        }
    }
    
    /**
     * Create logs table
     */
    private function create_logs_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(10) NOT NULL,
            message text NOT NULL,
            context longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY level (level),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Log emergency message
     *
     * @param string $message Message to log
     * @param array $context Context data
     */
    public function emergency($message, $context = []) {
        $this->log('emergency', $message, $context);
    }
    
    /**
     * Log alert message
     *
     * @param string $message Message to log
     * @param array $context Context data
     */
    public function alert($message, $context = []) {
        $this->log('alert', $message, $context);
    }
    
    /**
     * Log critical message
     *
     * @param string $message Message to log
     * @param array $context Context data
     */
    public function critical($message, $context = []) {
        $this->log('critical', $message, $context);
    }
    
    /**
     * Log error message
     *
     * @param string $message Message to log
     * @param array $context Context data
     */
    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }
    
    /**
     * Log warning message
     *
     * @param string $message Message to log
     * @param array $context Context data
     */
    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }
    
    /**
     * Log notice message
     *
     * @param string $message Message to log
     * @param array $context Context data
     */
    public function notice($message, $context = []) {
        $this->log('notice', $message, $context);
    }
    
    /**
     * Log info message
     *
     * @param string $message Message to log
     * @param array $context Context data
     */
    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }
    
    /**
     * Log debug message
     *
     * @param string $message Message to log
     * @param array $context Context data
     */
    public function debug($message, $context = []) {
        $this->log('debug', $message, $context);
    }
    
    /**
     * Add log entry
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function log($level, $message, $context = []) {
        if (!in_array($level, array_keys($this->levels))) {
            $level = 'info';
        }
        
        // Only log if enabled
        $min_level = get_option('wpwprintifysync_min_log_level', 'error');
        if ($this->levels[$level] > $this->levels[$min_level]) {
            return;
        }
        
        $log_entry = [
            'timestamp' => $this->timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
        
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
        
        $wpdb->insert(
            $this->table_name,
            [
                'level' => $entry['level'],
                'message' => $entry['message'],
                'context' => is_array($entry['context']) ? json_encode($entry['context']) : '',
                'created_at' => $entry['timestamp']
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );
    }
    
    /**
     * Log to file
     *
     * @param array $entry Log entry data
     */
    private function log_to_file($entry) {
        $log_dir = WP_CONTENT_DIR . '/logs/printify-sync/';
        
        // Create directory if it doesn't exist
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        // Create log file name based on date
        $log_file = $log_dir . 'log-' . date('Y-m-d') . '.log';
        
        // Format log entry
        $formatted_entry = sprintf(
            '[%s] %s: %s %s',
            $entry['timestamp'],
            strtoupper($entry['level']),
            $entry['message'],
            !empty($entry['context']) ? json_encode($entry['context']) : ''
        );
        
        // Append to log file
        file_put_contents($log_file, $formatted_entry . PHP_EOL, FILE_APPEND);
    }
}