<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Logger class for WP WooCommerce Printify Sync
 */
class Logger {
    /**
     * Log type (e.g., 'api', 'import', 'general')
     *
     * @var string
     */
    private $type;
    
    /**
     * Log option name prefix in database
     *
     * @var string
     */
    private $option_prefix = 'wpwps_log_';
    
    /**
     * Maximum number of log entries to keep
     *
     * @var int
     */
    private $max_entries = 1000;
    
    /**
     * Constructor
     *
     * @param string $type Log type
     */
    public function __construct($type = 'general') {
        $this->type = sanitize_key($type);
    }
    
    /**
     * Add log entry
     *
     * @param string $message Log message
     * @param string $level Log level (info, warning, error, debug)
     * @return bool Success status
     */
    public function log($message, $level = 'info') {
        $logs = $this->getLogs();
        
        // Add new log entry
        $logs[] = [
            'timestamp' => current_time('mysql'),
            'datetime' => date('Y-m-d H:i:s'),
            'message' => $message,
            'level' => $level,
            'user' => get_current_user_id(),
        ];
        
        // Trim logs if needed
        if (count($logs) > $this->max_entries) {
            $logs = array_slice($logs, -$this->max_entries);
        }
        
        // Save logs
        return update_option($this->getOptionName(), $logs);
    }
    
    /**
     * Get all logs of current type
     *
     * @return array Logs
     */
    public function getLogs() {
        return get_option($this->getOptionName(), []);
    }
    
    /**
     * Clear logs of current type
     *
     * @return bool Success status
     */
    public function clearLogs() {
        return delete_option($this->getOptionName());
    }
    
    /**
     * Get option name for current log type
     *
     * @return string Option name
     */
    private function getOptionName() {
        return $this->option_prefix . $this->type;
    }
}