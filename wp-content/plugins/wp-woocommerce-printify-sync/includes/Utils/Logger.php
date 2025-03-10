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
class Logger {
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
     * Log file path
     *
     * @var string
     */
    protected $log_file;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set minimum log level from settings (default to 'info')
        $this->minimum_level = get_option('apolloweb_printify_log_level', 'info');
        
        // Set log file path
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/apolloweb-printify/logs/plugin.log';
        
        // Ensure log directory exists
        $this->ensureLogDirectory();
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
        
        // Write to file
        $this->writeToFile($entry);
        
        // Also log to error_log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($message . ($context ? ' ' . json_encode($context) : ''));
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
        error_log($entry, 3, $this->log_file);
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