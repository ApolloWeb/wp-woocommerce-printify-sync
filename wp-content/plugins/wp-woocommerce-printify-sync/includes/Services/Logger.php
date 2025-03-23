<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Handles plugin logging
 */
class Logger {
    /**
     * Log a message
     *
     * @param string $message Message to log
     * @param string $level Log level (debug, info, warning, error)
     * @return bool Success
     */
    public function log(string $message, string $level = 'info'): bool {
        if (!$this->isLoggingEnabled()) {
            return false;
        }
        
        $log_entry = [
            'time' => current_time('mysql'),
            'level' => $level,
            'message' => $message
        ];
        
        $logs = $this->getLogs();
        $logs[] = $log_entry;
        
        // Keep only the last 1000 logs
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        // Also log to WC_Logger if WooCommerce is active
        if (function_exists('wc_get_logger')) {
            $wc_logger = wc_get_logger();
            $wc_logger->log($level, $message, ['source' => 'wpwps']);
        }
        
        return update_option('wpwps_logs', $logs);
    }
    
    /**
     * Get all logs
     *
     * @return array Logs
     */
    public function getLogs(): array {
        return get_option('wpwps_logs', []);
    }
    
    /**
     * Clear all logs
     *
     * @return bool Success
     */
    public function clearLogs(): bool {
        return delete_option('wpwps_logs');
    }
    
    /**
     * Check if logging is enabled
     *
     * @return bool Whether logging is enabled
     */
    private function isLoggingEnabled(): bool {
        return get_option('wpwps_enable_logging', 'yes') === 'yes';
    }
}
