<?php
/**
 * Logger class for plugin logging
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utility
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utility;

class Logger {
    /**
     * @var string Log level
     */
    private $logLevel;
    
    /**
     * @var array Valid log levels
     */
    private $validLogLevels = ['debug', 'info', 'warning', 'error', 'critical'];
    
    /**
     * @var array Log level priorities (higher number = more severe)
     */
    private $logLevelPriorities = [
        'debug'    => 1,
        'info'     => 2,
        'warning'  => 3,
        'error'    => 4,
        'critical' => 5
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->logLevel = get_option('wpwprintifysync_log_level', 'info');
        
        if (!in_array($this->logLevel, $this->validLogLevels)) {
            $this->logLevel = 'info';
        }
    }
    
    /**
     * Log a message
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return bool Whether log was written
     */
    private function log($level, $message, $context = []) {
        if (!in_array($level, $this->validLogLevels)) {
            return false;
        }
        
        // Check if current log level priority is high enough
        if ($this->logLevelPriorities[$level] < $this->logLevelPriorities[$this->logLevel]) {
            return false;
        }
        
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpwprintifysync_logs';
        
        // If table doesn't exist yet, write to file as fallback
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return $this->writeToFile($level, $message, $context);
        }
        
        // Insert log into database
        $result = $wpdb->insert(
            $table,
            [
                'level'      => $level,
                'message'    => $message,
                'context'    => is_array($context) ? json_encode($context) : $context,
                'created_at' => current_time('mysql')
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );
        
        return ($result !== false);
    }
    
    /**
     * Write log to file (fallback method)
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return bool Whether log was written
     */
    private function writeToFile($level, $message, $context = []) {
        $upload_dir = wp_upload_dir();
        $logs_dir = $upload_dir['basedir'] . '/wp-woocommerce-printify-sync/logs';
        
        // Create directory if it doesn't exist
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
        }
        
        $date = date('Y-m-d');
        $log_file = $logs_dir . '/log-' . $date . '.log';
        
        $time = date('Y-m-d H:i:s');
        $entry = "[{$time}] [{$level}] {$message}";
        
        if (!empty($context)) {
            $entry .= ' ' . json_encode($context);
        }
        
        $entry .= PHP_EOL;
        
        return (file_put_contents($log_file, $entry, FILE_APPEND) !== false);
    }
    
    /**
     * Debug log
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return bool Whether log was written
     */
    public function debug($message, $context = []) {
        return $this->log('debug', $message, $context);
    }
    
    /**
     * Info log
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return bool Whether log was written
     */
    public function info($message, $context = []) {
        return $this->log('info', $message, $context);
    }
    
    /**
     * Warning log
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return bool Whether log was written
     */
    public function warning($message, $context = []) {
        return $this->log('warning', $message, $context);
    }
    
    /**
     * Error log
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @return bool Whether log was written
     */
    public function error($message, $context = []) {
        return $this->log('error', $message, $context