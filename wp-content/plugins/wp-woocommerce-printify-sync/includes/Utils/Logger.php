<?php
/**
 * Logger Implementation
 *
 * Handles logging for the plugin.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utils
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utils;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

/**
 * Logger Class
 */
class Logger implements LoggerInterface {
    /**
     * Log levels
     *
     * @var array
     */
    protected $levels = [
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
     * Minimum log level to record
     *
     * @var string
     */
    protected $minimum_level;
    
    /**
     * Table name for database logs
     *
     * @var string
     */
    protected $table_name;
    
    /**
     * Whether to write logs to file
     *
     * @var bool
     */
    protected $log_to_file;
    
    /**
     * Whether to write logs to database
     *
     * @var bool
     */
    protected $log_to_db;
    
    /**
     * Log file path
     *
     * @var string
     */
    protected $log_file;
    
    /**
     * Maximum log file size in bytes (5MB default)
     *
     * @var int
     */
    protected $max_file_size = 5242880;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        // Set minimum log level from settings (default to 'info')
        $this->minimum_level = get_option('apolloweb_printify_log_level', 'info');
        
        // Set logging destinations from settings
        $this->log_to_file = 'yes' === get_option('apolloweb_printify_log_to_file', 'yes');
        $this->log_to_db = 'yes' === get_option('apolloweb_printify_log_to_db', 'yes');
        
        // Set table name using the database prefix
        $this->table_name = $wpdb->prefix . 'apolloweb_printify_logs';
        
        // Set log file path
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/apolloweb-printify/logs/plugin.log';
        
        // Ensure log directory exists
        if ($this->log_to_file) {
            $this->ensureLogDirectory();
        }
        
        // Ensure log table exists
        if ($this->log_to_db) {
            $this->ensureLogTable();
        }
    }
    
    /**
     * Ensure log directory exists
     *
     * @return void
     */
    protected function ensureLogDirectory() {
        $log_dir = dirname($this->log_file);
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Create .htaccess file to prevent direct access
            file_put_contents($log_dir . '/.htaccess', 'Deny from all');
            
            // Create index.php to prevent directory listing
            file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
        }
    }
    
    /**
     * Ensure log table exists
     *
     * @return void
     */
    protected function ensureLogTable() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY level (level),
            KEY timestamp (timestamp),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Log a message
     *
     * @param string $message Log message
     * @param string $level Log level
     * @param array  $context Additional context
     * @return void
     */
    public function log($message, $level = 'info', $context = []) {
        // Check if this level should be logged
        if (!$this->shouldLog($level)) {
            return;
        }
        
        // Format log entry
        $entry = $this->formatLogEntry($message, $level, $context);
        
        // Write to file if enabled
        if ($this->log_to_file) {
            $this->writeToFile($entry);
        }
        
        // Write to database if enabled
        if ($this->log_to_db) {
            $this->writeToDatabase($message, $level, $context);
        }
    }
    
    /**
     * Check if a log level should be recorded
     *
     * @param string $level Log level
     * @return bool
     */
    protected function shouldLog($level) {
        // If level doesn't exist, use default level
        if (!isset($this->levels[$level])) {
            $level = 'info';
        }
        
        // If minimum level doesn't exist, use default level
        if (!isset($this->levels[$this->minimum_level])) {
            $this->minimum_level = 'info';
        }
        
        // Check if level should be logged
        return $this->levels[$level] <= $this->levels[$this->minimum_level];
    }
    
    /**
     * Format a log entry for file writing
     *
     * @param string $message Log message
     * @param string $level Log level
     * @param array  $context Additional context
     * @return string
     */
    protected function formatLogEntry($message, $level, $context = []) {
        $time = current_time('mysql');
        $level_upper = strtoupper($level);
        
        $entry = "[$time] [$level_upper] $message";
        
        // Add context if provided
        if (!empty($context)) {
            $entry .= " " . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        
        return $entry . PHP_EOL;
    }
    
    /**
     * Write log entry to file
     *
     * @param string $entry Log entry
     * @return void
     */
    protected function writeToFile($entry) {
        // Rotate log if necessary
        $this->rotateLogFileIfNeeded();
        
        // Write to log file
        error_log($entry, 3, $this->log_file);
    }
    
    /**
     * Write log entry to database
     *
     * @param string $message Log message
     * @param string $level Log level
     * @param array  $context Additional context
     * @return void
     */
    protected function writeToDatabase($message, $level, $context = []) {
        global $wpdb;
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Get IP address
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Insert log entry
        $wpdb->insert(
            $this->table_name,
            [
                'timestamp'  => current_time('mysql'),
                'level'      => $level,
                'message'    => $message,
                'context'    => !empty($context) ? json_encode($context) : null,
                'user_id'    => $user_id ? $user_id : null,
                'ip_address' => $ip_address,
            ],
            [
                '%s', // timestamp
                '%s', // level
                '%s', // message
                '%s', // context
                '%d', // user_id
                '%s', // ip_address
            ]
        );
    }
    
    /**
     * Rotate log file if it exceeds the maximum size
     *
     * @return void
     */
    protected function rotateLogFileIfNeeded() {
        if (!file_exists($this->log_file)) {
            return;
        }
        
        // Check file size
        $file_size = filesize($this->log_file);
        
        if ($file_size < $this->max_file_size) {
            return;
        }
        
        // Rotate log file
        $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s');
        rename($this->log_file, $backup_file);
        
        // Compress backup file
        if (function_exists('gzopen')) {
            $gz_file = $backup_file . '.gz';
            $gz = gzopen($gz_file, 'w9');
            gzwrite($gz, file_get_contents($backup_file));
            gzclose($gz);
            
            // Delete original backup file
            unlink($backup_file);
        }
    }
    
    /**
     * Log an error message
     *
     * @param string $message Error message
     * @param array  $context Additional context
     * @return void
     */
    public function error($message, $context = []) {
        $this->log($message, 'error', $context);
    }
    
    /**
     * Log a warning message
     *
     * @param string $message Warning message
     * @param array  $context Additional context
     * @return void
     */
    public function warning($message, $context = []) {
        $this->log($message, 'warning', $context);
    }
    
    /**
     * Log an info message
     *
     * @param string $message Info message
     * @param array  $context Additional context
     * @return void
     */
    public function info($message, $context = []) {
        $this->log($message, 'info', $context);
    }
    
    /**
     * Log a debug message
     *
     * @param string $message Debug message
     * @param array  $context Additional context
     * @return void
     */
    public function debug($message, $context = []) {
        $this->log($message, 'debug', $context);
    }
}