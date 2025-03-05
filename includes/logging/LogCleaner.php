<?php
/**
 * Log Cleaner class for maintenance tasks
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Logging
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Logging;

class LogCleaner {
    private static $instance = null;
    private $timestamp = '2025-03-05 19:13:30';
    
    /**
     * Get single instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register cleanup hook
        add_action('wpwprintifysync_cleanup_logs', [$this, 'scheduledCleanup']);
    }
    
    /**
     * Clean logs older than specified days
     */
    public function cleanLogs($days = 14) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwprintifysync_logs';
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // First, export logs to file before deletion (optional)
        if (get_option('wpwprintifysync_export_before_delete', true)) {
            $old_logs = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$table_name}
                WHERE created_at < %s
                ORDER BY created_at ASC
            ", $cutoff_date));
            
            if (!empty($old_logs)) {
                // Archive logs to file
                $this->archiveLogsToFile($old_logs);
                
                // Optionally upload to cloud storage
                if (get_option('wpwprintifysync_cloud_backup', false)) {
                    LogExporter::getInstance()->exportLogsToCloud('', $cutoff_date, '', '', '');
                }
            }
        }
        
        // Delete old logs
        $deleted = $wpdb->query($wpdb->prepare("
            DELETE FROM {$table_name}
            WHERE created_at < %s
        ", $cutoff_date));
        
        return $deleted;
    }
    
    /**
     * Clean file logs older than specified days
     */
    public function cleanFileLogs($days = 30) {
        $log_dir = WP_CONTENT_DIR . '/printify-sync-logs';
        
        if (!file_exists($log_dir) || !is_dir($log_dir)) {
            return 0;
        }
        
        $deleted = 0;
        $cutoff_timestamp = strtotime("-{$days} days");
        
        // Get all year directories
        $year_dirs = glob($log_dir . '/????', GLOB_ONLYDIR);
        foreach ($year_dirs as $year_dir) {
            // Get all month directories
            $month_dirs = glob($year_dir . '/??', GLOB_ONLYDIR);
            foreach ($month_dirs as $month_dir) {
                // Get all day directories
                $day_dirs = glob($month_dir . '/??', GLOB_ONLYDIR);
                foreach ($day_dirs as $day_dir) {
                    // Get directory date from path
                    $dir_date = basename($year_dir) . '-' . basename($month_dir) . '-' . basename($day_dir);
                    $dir_timestamp = strtotime($dir_date);
                    
                    // If directory is older than cutoff, delete it
                    if ($dir_timestamp < $cutoff_timestamp) {
                        $day_logs = glob($day_dir . '/*.log');
                        foreach ($day_logs as $log_file) {
                            @unlink($log_file);
                            $deleted++;
                        }
                        
                        @rmdir($day_dir);
                    }
                }
                
                // Remove month directory if empty
                $this->removeEmptyDir($month_dir);
            }
            
            // Remove year directory if empty
            $this->removeEmptyDir($year_dir);
        }
        
        return $deleted;
    }
    
    /**
     * Remove empty directory
     */
    private function removeEmptyDir($dir) {
        if (file_exists($dir) && is_dir($dir)) {
            $files = scandir($dir);
            if (count($files) <= 2) { // Only . and .. entries
                @rmdir($dir);
            }
        }
    }
    
    /**
     * Scheduled cleanup task
     */
    public function scheduledCleanup() {
        // Clean database logs
        $retention_days = get_option('wpwprintifysync_log_retention_days', 14);
        $this->cleanLogs($retention_days);
        
        // Clean file logs
        $file_retention_days = get_option('wpwprintifysync_file_log_retention_days', 30);
        $this->cleanFileLogs($file_retention_days);
        
        // Log the cleanup
        Logger::getInstance()->info('Performed scheduled log cleanup', [
            'database_retention_days' => $retention_days,
            'file_retention_days' => $file_retention_days,
            'timestamp' => $this->timestamp
        ]);
    }
    
    /**
     * Archive logs to file before deletion
     */
    private function archiveLogsToFile($logs) {
        if (empty($logs)) {
            return false;
        }
        
        // Create archive directory
        $archive_dir = WP_CONTENT_DIR . '/printify-sync-logs/archive';
        if (!file_exists($archive_dir)) {
            wp_mkdir_p($archive_dir);
            
            // Create .htaccess to prevent direct access
            file_put_contents($archive_dir . '/.htaccess', "Deny from all");
        }
        
        // Create archive file
        $filename = 'logs-archive-' . date('YmdHis') . '.json';
        $file_path = $archive_dir . '/' . $filename;
        
        // Convert logs to JSON
        $json_data = json_encode($logs, JSON_PRETTY_PRINT);
        
        // Write to file
        return file_put_contents($file_path, $json_data) !== false;
    }
}