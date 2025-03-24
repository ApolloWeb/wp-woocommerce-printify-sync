<?php
/**
 * Logger Service
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Class LoggerService
 *
 * Handles logging for the plugin
 */
class LoggerService
{
    /**
     * Log levels
     */
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    /**
     * Log a debug message
     *
     * @param string $message The message to log
     * @param array  $context Additional context data
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log an info message
     *
     * @param string $message The message to log
     * @param array  $context Additional context data
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log a warning message
     *
     * @param string $message The message to log
     * @param array  $context Additional context data
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log an error message
     *
     * @param string $message The message to log
     * @param array  $context Additional context data
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log a message
     *
     * @param string $level   Log level
     * @param string $message The message to log
     * @param array  $context Additional context data
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $log_dir = WPWPS_PLUGIN_DIR . 'logs';

        // Create logs directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Get current date for log file
        $date = date('Y-m-d');
        $log_file = $log_dir . '/wpwps-' . $date . '.log';

        // Format log entry
        $timestamp = date('Y-m-d H:i:s');
        $formatted_message = sprintf(
            '[%s] [%s] %s %s' . PHP_EOL,
            $timestamp,
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        );

        // Write to log file
        error_log($formatted_message, 3, $log_file);

        // Clean up old logs
        $this->cleanOldLogs();
    }

    /**
     * Clean up old log files
     *
     * @return void
     */
    private function cleanOldLogs(): void
    {
        // Clean logs only once a day using transients
        $transient_name = 'wpwps_logs_cleaned';
        if (get_transient($transient_name)) {
            return;
        }

        // Get log retention days from settings
        $retention_days = get_option('wpwps_log_retention_days', 30);
        $log_dir = WPWPS_PLUGIN_DIR . 'logs';

        // Skip if logs directory doesn't exist
        if (!file_exists($log_dir) || !is_dir($log_dir)) {
            return;
        }

        // Get all log files
        $files = glob($log_dir . '/wpwps-*.log');
        if (empty($files)) {
            return;
        }

        // Calculate the cutoff date
        $cutoff = strtotime('-' . $retention_days . ' days');

        // Delete files older than the cutoff
        foreach ($files as $file) {
            // Extract date from filename
            if (preg_match('/wpwps-(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
                $file_date = strtotime($matches[1]);
                if ($file_date && $file_date < $cutoff) {
                    unlink($file);
                }
            }
        }

        // Set the transient to avoid cleaning again today
        set_transient($transient_name, true, DAY_IN_SECONDS);
    }
}
