<?php
/**
 * Logger service for handling logging.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Logger service for centralized logging.
 */
class Logger
{
    /**
     * Available log levels.
     *
     * @var array
     */
    private $levels = [
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
     * Log file path.
     *
     * @var string
     */
    private $log_dir;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->log_dir = WPWPS_PLUGIN_DIR . 'logs/';

        // Create logs directory if it doesn't exist
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);

            // Create .htaccess to protect log files
            $htaccess = "# Disable directory browsing\nOptions -Indexes\n\n# Deny access to all files\n<FilesMatch \".*\">\nOrder Allow,Deny\nDeny from all\n</FilesMatch>";
            file_put_contents($this->log_dir . '.htaccess', $htaccess);
        }
    }

    /**
     * Log a message.
     *
     * @param string $message Message to log.
     * @param string $level   Log level.
     * @param array  $context Additional context.
     * @return bool Whether the message was logged.
     */
    public function log($message, $level = 'info', $context = [])
    {
        if (!array_key_exists($level, $this->levels)) {
            $level = 'info';
        }

        // Check if we should log this level based on settings
        $log_level = get_option('wpwps_log_level', 'info');
        if (!array_key_exists($log_level, $this->levels)) {
            $log_level = 'info';
        }

        if ($this->levels[$level] > $this->levels[$log_level]) {
            return false;
        }

        $timestamp = date('Y-m-d H:i:s');
        $log_file = $this->getLogFile();

        $log_entry = "[{$timestamp}] [{$level}] {$message}";

        if (!empty($context)) {
            $log_entry .= " " . json_encode($context);
        }

        $log_entry .= PHP_EOL;

        // Write to log file
        $result = file_put_contents($log_file, $log_entry, FILE_APPEND);

        // If logging fails or file size exceeds limit, rotate logs
        if ($result === false || filesize($log_file) > 10 * 1024 * 1024) {
            $this->rotateLogFiles();
            file_put_contents($this->getLogFile(), $log_entry, FILE_APPEND);
        }

        return true;
    }

    /**
     * Log an emergency message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool Whether the message was logged.
     */
    public function emergency($message, $context = [])
    {
        return $this->log($message, 'emergency', $context);
    }

    /**
     * Log an alert message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool Whether the message was logged.
     */
    public function alert($message, $context = [])
    {
        return $this->log($message, 'alert', $context);
    }

    /**
     * Log a critical message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool Whether the message was logged.
     */
    public function critical($message, $context = [])
    {
        return $this->log($message, 'critical', $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool Whether the message was logged.
     */
    public function error($message, $context = [])
    {
        return $this->log($message, 'error', $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool Whether the message was logged.
     */
    public function warning($message, $context = [])
    {
        return $this->log($message, 'warning', $context);
    }

    /**
     * Log a notice message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool Whether the message was logged.
     */
    public function notice($message, $context = [])
    {
        return $this->log($message, 'notice', $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool Whether the message was logged.
     */
    public function info($message, $context = [])
    {
        return $this->log($message, 'info', $context);
    }

    /**
     * Log a debug message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context.
     * @return bool Whether the message was logged.
     */
    public function debug($message, $context = [])
    {
        return $this->log($message, 'debug', $context);
    }

    /**
     * Get the log file path.
     *
     * @return string Log file path.
     */
    private function getLogFile()
    {
        return $this->log_dir . 'wpwps-' . date('Y-m-d') . '.log';
    }

    /**
     * Rotate log files to keep disk usage under control.
     *
     * @return void
     */
    private function rotateLogFiles()
    {
        $log_files = glob($this->log_dir . 'wpwps-*.log');
        
        // Sort by modification time (newest first)
        usort($log_files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Keep only the 5 most recent files
        for ($i = 5; $i < count($log_files); $i++) {
            if (file_exists($log_files[$i])) {
                unlink($log_files[$i]);
            }
        }
    }

    /**
     * Get log entries for display in the admin area.
     *
     * @param string $level       Log level to filter by.
     * @param int    $limit       Maximum number of entries to return.
     * @param string $search_term Search term to filter by.
     * @return array Log entries.
     */
    public function getLogEntries($level = null, $limit = 100, $search_term = '')
    {
        $log_file = $this->getLogFile();
        $entries = [];

        if (!file_exists($log_file)) {
            return $entries;
        }

        $file = fopen($log_file, 'r');
        if ($file) {
            $line_count = 0;
            $lines = [];

            // Read all lines from the file
            while (($line = fgets($file)) !== false) {
                $lines[] = $line;
                $line_count++;
            }
            fclose($file);

            // Process lines in reverse order (newest first)
            $lines = array_reverse($lines);
            $count = 0;

            foreach ($lines as $line) {
                // Parse the log entry
                if (preg_match('/^\[(.*?)\] \[(.*?)\] (.*)$/', $line, $matches)) {
                    $timestamp = $matches[1];
                    $log_level = $matches[2];
                    $message = $matches[3];

                    // Filter by level if specified
                    if ($level && $level !== $log_level) {
                        continue;
                    }

                    // Filter by search term if specified
                    if ($search_term && stripos($message, $search_term) === false) {
                        continue;
                    }

                    $entries[] = [
                        'timestamp' => $timestamp,
                        'level' => $log_level,
                        'message' => $message,
                    ];

                    $count++;
                    if ($count >= $limit) {
                        break;
                    }
                }
            }
        }

        return $entries;
    }

    /**
     * Clear logs via AJAX.
     *
     * @return void
     */
    public function clearLogsAjax()
    {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }
        
        $result = $this->clearLogs();
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Logs cleared successfully.', 'wp-woocommerce-printify-sync'),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to clear logs.', 'wp-woocommerce-printify-sync'),
            ]);
        }
    }

    /**
     * Clear all log files.
     *
     * @return bool Whether the logs were cleared.
     */
    public function clearLogs()
    {
        $log_files = glob($this->log_dir . 'wpwps-*.log');
        $success = true;

        foreach ($log_files as $file) {
            if (file_exists($file)) {
                $result = unlink($file);
                if (!$result) {
                    $success = false;
                }
            }
        }

        return $success;
    }
}
