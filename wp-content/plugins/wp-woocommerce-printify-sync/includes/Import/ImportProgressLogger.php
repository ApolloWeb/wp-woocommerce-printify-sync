<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Import;

/**
 * Class to log import progress
 */
class ImportProgressLogger
{
    /**
     * Log import activity
     * 
     * @param string $productId Product ID
     * @param string $status Status of the import (success, pending, failed)
     * @param string $message Message to log
     * @param array $data Additional data to log
     * @return void
     */
    public static function log(string $productId, string $status, string $message, array $data = []): void
    {
        $logs = get_option('wpwps_import_logs', []);
        
        // Keep only the last 100 logs
        if (count($logs) > 100) {
            array_shift($logs);
        }
        
        $logs[] = [
            'product_id' => $productId,
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'timestamp' => current_time('mysql')
        ];
        
        update_option('wpwps_import_logs', $logs);
    }
    
    /**
     * Get import logs
     * 
     * @param int $limit Number of logs to return
     * @return array Import logs
     */
    public static function getLogs(int $limit = 0): array
    {
        $logs = get_option('wpwps_import_logs', []);
        
        // Sort logs by timestamp, newest first
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Limit the number of logs if requested
        if ($limit > 0 && count($logs) > $limit) {
            $logs = array_slice($logs, 0, $limit);
        }
        
        return $logs;
    }
    
    /**
     * Clear all logs
     * 
     * @return void
     */
    public static function clearLogs(): void
    {
        delete_option('wpwps_import_logs');
    }
}
