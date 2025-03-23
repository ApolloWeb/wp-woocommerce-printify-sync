<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Logger class for handling plugin logging
 */
class Logger {
    /**
     * Log a message
     *
     * @param string $message The message to log
     * @param string $level The log level (debug, info, warning, error)
     * @return bool Whether the log was recorded
     */
    public function log(string $message, string $level = 'info'): bool {
        if (!$this->isLoggingEnabled()) {
            return false;
        }
        
        // Use WC_Logger if available
        if (function_exists('wc_get_logger')) {
            $wc_logger = wc_get_logger();
            $wc_logger->log($level, $message, ['source' => 'wpwps']);
        }
        
        // Also store in our own logs for the admin dashboard
        $logs = get_option('wpwps_logs', []);
        
        $logs[] = [
            'time' => current_time('mysql'),
            'level' => $level,
            'message' => $message
        ];
        
        // Keep the log at a reasonable size (last 1000 entries)
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        return update_option('wpwps_logs', $logs);
    }
    
    /**
     * Get all logs
     *
     * @param int $limit Number of logs to retrieve (0 for all)
     * @return array Array of logs
     */
    public function getLogs(int $limit = 0): array {
        $logs = get_option('wpwps_logs', []);
        
        // Sort by time descending
        usort($logs, function($a, $b) {
            return strtotime($b['time']) <=> strtotime($a['time']);
        });
        
        // Limit if requested
        if ($limit > 0 && count($logs) > $limit) {
            $logs = array_slice($logs, 0, $limit);
        }
        
        return $logs;
    }
    
    /**
     * Clear all logs
     *
     * @return bool Whether logs were cleared
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
