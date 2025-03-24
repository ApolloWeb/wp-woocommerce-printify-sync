<?php
/**
 * Logger utility class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Class Logger
 */
class Logger {
    /**
     * Log type
     *
     * @var string
     */
    private $type;
    
    /**
     * Log file
     *
     * @var string
     */
    private $file;
    
    /**
     * Constructor
     *
     * @param string $type Log type.
     */
    public function __construct($type = 'general') {
        $this->type = sanitize_file_name($type);
        
        // Set up log directory
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/wpwps-logs';
        
        // Create log directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Add index.php for security
            file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
            
            // Add .htaccess for security
            file_put_contents($log_dir . '/.htaccess', 'Deny from all');
        }
        
        // Set log file
        $this->file = $log_dir . '/' . $this->type . '-' . date('Y-m-d') . '.log';
    }
    
    /**
     * Log a message
     *
     * @param string $message Message to log.
     * @param string $level Log level.
     * @return bool Success status.
     */
    public function log($message, $level = 'info') {
        if (empty($message)) {
            return false;
        }
        
        // Format message
        $log_message = sprintf(
            '[%s] [%s] %s' . PHP_EOL,
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );
        
        // Write to log file
        return file_put_contents($this->file, $log_message, FILE_APPEND);
    }
    
    /**
     * Log an error
     *
     * @param string $message Error message.
     * @return bool Success status.
     */
    public function error($message) {
        return $this->log($message, 'error');
    }
    
    /**
     * Log a warning
     *
     * @param string $message Warning message.
     * @return bool Success status.
     */
    public function warning($message) {
        return $this->log($message, 'warning');
    }
    
    /**
     * Log an info message
     *
     * @param string $message Info message.
     * @return bool Success status.
     */
    public function info($message) {
        return $this->log($message, 'info');
    }
    
    /**
     * Log a debug message
     *
     * @param string $message Debug message.
     * @return bool Success status.
     */
    public function debug($message) {
        return $this->log($message, 'debug');
    }
}
