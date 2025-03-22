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
     * Log levels.
     */
    const DEBUG = 'debug';
    const INFO = 'info';
    const NOTICE = 'notice';
    const WARNING = 'warning';
    const ERROR = 'error';
    const CRITICAL = 'critical';
    const ALERT = 'alert';
    const EMERGENCY = 'emergency';

    /**
     * Log directory.
     */
    private $log_dir;

    /**
     * Current log level.
     */
    private $log_level;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->log_dir = WPWPS_PLUGIN_DIR . 'logs/';
        $this->log_level = get_option('wpwps_log_level', self::INFO);

        // Create logs directory if it doesn't exist
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);

            // Add .htaccess to protect logs
            $htaccess = "Order Deny,Allow\nDeny from all\n";
            file_put_contents($this->log_dir . '.htaccess', $htaccess);
        }
    }

    /**
     * Log a message.
     *
     * @param string $level   Log level.
     * @param string $message Log message.
     * @param array  $context Optional context data.
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $entry = [
            'timestamp' => current_time('mysql'),
            'level'     => $level,
            'message'   => $this->interpolate($message, $context),
            'context'   => $context
        ];

        // Write to daily log file
        $filename = $this->log_dir . date('Y-m-d') . '.log';
        $line = json_encode($entry) . "\n";

        file_put_contents($filename, $line, FILE_APPEND);
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
        return $this->log(self::EMERGENCY, $message, $context);
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
        return $this->log(self::ALERT, $message, $context);
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
        return $this->log(self::CRITICAL, $message, $context);
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
        return $this->log(self::ERROR, $message, $context);
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
        return $this->log(self::WARNING, $message, $context);
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
        return $this->log(self::NOTICE, $message, $context);
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
        return $this->log(self::INFO, $message, $context);
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
        return $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Interpolate context values into message placeholders.
     *
     * @param string $message Log message with placeholders.
     * @param array  $context Context data to replace placeholders.
     * @return string Interpolated message.
     */
    private function interpolate($message, array $context = [])
    {
        if (empty($context)) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $val) {
            if (is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Check if the given level should be logged.
     *
     * @param string $level Log level to check.
     * @return bool Whether the level should be logged.
     */
    private function shouldLog($level)
    {
        $levels = [
            self::DEBUG     => 100,
            self::INFO      => 200,
            self::NOTICE    => 250,
            self::WARNING   => 300,
            self::ERROR     => 400,
            self::CRITICAL  => 500,
            self::ALERT     => 550,
            self::EMERGENCY => 600,
        ];

        return $levels[$level] >= $levels[$this->log_level];
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
        usort($log_files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Keep only the 5 most recent files
        for ($i = 5; $i < count($log_files); $i++) {
            if (file_exists($log_files[$i])) {
                unlink($log_files[$i]);
            }
        }
    }
}
