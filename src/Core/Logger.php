<?php
/**
 * Logger Class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Handles logging and error tracking
 */
class Logger {
    /**
     * Log levels
     */
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    
    /**
     * Log file path
     *
     * @var string
     */
    private $log_file;
    
    /**
     * Whether to log to database
     *
     * @var bool
     */
    private $log_to_db;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set log file path
        $uploads_dir = wp_upload_dir();
        $this->log_file = trailingslashit($uploads_dir['basedir']) . 'wpwps-logs/wpwps-' . date('Y-m-d') . '.log';
        
        // Create log directory if it doesn't exist
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Check if we should log to database
        $this->log_to_db = apply_filters('wpwps_log_to_db', true);
    }
    
    /**
     * Log a message
     *
     * @param string $level   Log level
     * @param string $message Log message
     * @param array  $context Additional context
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void {
        // Format the log message
        $formatted = $this->formatLogMessage($level, $message, $context);
        
        // Write to file
        $this->writeToFile($formatted);
        
        // Write to database if enabled
        if ($this->log_to_db) {
            $this->writeToDatabase($level, $message, $context);
        }
    }
    
    /**
     * Log emergency message
     *
     * @param string $message Log message
     * @param array  $context Additional context
     * @return void
     */
    public function emergency(string $message, array $context = []): void {
        $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Log alert message
     *
     * @param string $message Log message
     * @param array  $context Additional context
     * @return void
     */
    public function alert(string $message, array $context = []): void {
        $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * Log critical message
     *
     * @param string $message Log message
     * @param array  $context Additional context
     * @return void
     */
    public function critical(string $message, array $context = []): void {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array  $context Additional context
     * @return void
     */
    public function error(string $message, array $context = []): void {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array  $context Additional context
     * @return void
     */
    public function warning(string $message, array $context = []): void {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log notice message
     *
     * @param string $message Log message
     * @param array  $context Additional context
     * @return void
     */
    public function notice(string $message, array $context = []): void {
        $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array  $context Additional context
     * @return void
     */
    public function info(string $message, array $context = []): void {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array  $context Additional context
     * @return void
     */
    public function debug(string $message, array $context = []): void {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Format log message
     *
     * @param string $level   Log level
     * @param string $message Log message
     * @param array  $context Additional context
     * @return string Formatted log message
     */
    private function formatLogMessage(string $level, string $message, array $context = []): string {
        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[{$timestamp}] [{$level}] {$message}";
        
        // Add context if available
        if (!empty($context)) {
            $formatted .= ' ' . json_encode($context);
        }
        
        return $formatted;
    }
    
    /**
     * Write log to file
     *
     * @param string $message Formatted log message
     * @return void
     */
    private function writeToFile(string $message): void {
        file_put_contents($this->log_file, $message . PHP_EOL, FILE_APPEND);
    }
    
    /**
     * Write log to database
     *
     * @param string $level   Log level
     * @param string $message Log message
     * @param array  $context Additional context
     * @return void
     */
    private function writeToDatabase(string $level, string $message, array $context = []): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_logs';
        
        // Ensure log table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $this->createLogTable();
        }
        
        // Insert log entry
        $wpdb->insert(
            $table_name,
            [
                'timestamp' => current_time('mysql'),
                'level'     => $level,
                'message'   => $message,
                'context'   => !empty($context) ? json_encode($context) : null,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
    }
    
    /**
     * Create log table
     *
     * @return void
     */
    private function createLogTable(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT NULL,
            level varchar(20) DEFAULT NULL,
            message text DEFAULT NULL,
            context text DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY level (level),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    /**
     * Rotate logs
     * 
     * @param int $days Number of days to keep logs
     * @return void
     */
    public function rotateLogs(int $days = 30): void {
        // Delete old log files
        $uploads_dir = wp_upload_dir();
        $log_dir = trailingslashit($uploads_dir['basedir']) . 'wpwps-logs';
        
        if (!is_dir($log_dir)) {
            return;
        }
        
        $cut_off = strtotime('-' . $days . ' days');
        
        $files = scandir($log_dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $file_path = $log_dir . '/' . $file;
            
            // Extract date from filename (format: wpwps-YYYY-MM-DD.log)
            if (preg_match('/wpwps-(\d{4}-\d{2}-\d{2})\.log/', $file, $matches)) {
                $file_date = strtotime($matches[1]);
                
                if ($file_date < $cut_off) {
                    @unlink($file_path);
                }
            }
        }
        
        // Delete old database entries
        if ($this->log_to_db) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wpwps_logs';
            
            $cut_off_date = date('Y-m-d H:i:s', $cut_off);
            
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $table_name WHERE timestamp < %s",
                    $cut_off_date
                )
            );
        }
    }
}
