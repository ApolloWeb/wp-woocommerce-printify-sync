<?php
/**
 * Logger.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

/**
 * Logger class.
 */
class Logger {
    /**
     * Log levels.
     *
     * @var array
     */
    private $levels = [
        'debug',
        'info',
        'notice',
        'warning',
        'error',
        'critical',
        'alert',
        'emergency',
    ];

    /**
     * Log file path.
     *
     * @var string
     */
    private $log_file;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->log_file = WPWPS_PLUGIN_DIR . 'logs/debug.log';

        // Create logs directory if it doesn't exist.
        if (!file_exists(dirname($this->log_file))) {
            wp_mkdir_p(dirname($this->log_file));
        }
    }

    /**
     * Log message.
     *
     * @param string $message Message to log.
     * @param array  $context Log context.
     * @param string $level   Log level.
     * @return void
     */
    public function log($message, $context = [], $level = 'info') {
        // Validate log level.
        if (!in_array($level, $this->levels, true)) {
            $level = 'info';
        }

        // Format log message.
        $log = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message;

        // Add context if provided.
        if (!empty($context)) {
            $log .= ' ' . wp_json_encode($context);
        }

        // Add new line.
        $log .= PHP_EOL;

        // Write to log file.
        error_log($log, 3, $this->log_file);

        // Also log to database for admin viewing.
        $this->logToDatabase($message, $context, $level);
    }

    /**
     * Log message to database.
     *
     * @param string $message Message to log.
     * @param array  $context Log context.
     * @param string $level   Log level.
     * @return void
     */
    private function logToDatabase($message, $context = [], $level = 'info') {
        global $wpdb;

        // Log API requests to the database.
        if (isset($context['endpoint']) && isset($context['method'])) {
            $table_name = $wpdb->prefix . 'wpwps_api_logs';
            
            // Make sure the API data is properly stored.
            $endpoint = isset($context['endpoint']) ? $context['endpoint'] : '';
            $method = isset($context['method']) ? $context['method'] : '';
            $request_data = isset($context['data']) ? wp_json_encode($context['data']) : '';
            $response_data = isset($context['response']) ? wp_json_encode($context['response']) : '';
            $status_code = isset($context['status_code']) ? intval($context['status_code']) : 0;

            // Insert into database.
            $wpdb->insert(
                $table_name,
                [
                    'endpoint' => $endpoint,
                    'request_data' => $request_data,
                    'response_data' => $response_data,
                    'status_code' => $status_code,
                    'created_at' => current_time('mysql'),
                ],
                [
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                ]
            );
        }
    }

    /**
     * Debug log.
     *
     * @param string $message Message to log.
     * @param array  $context Log context.
     * @return void
     */
    public function debug($message, $context = []) {
        $this->log($message, $context, 'debug');
    }

    /**
     * Info log.
     *
     * @param string $message Message to log.
     * @param array  $context Log context.
     * @return void
     */
    public function info($message, $context = []) {
        $this->log($message, $context, 'info');
    }

    /**
     * Warning log.
     *
     * @param string $message Message to log.
     * @param array  $context Log context.
     * @return void
     */
    public function warning($message, $context = []) {
        $this->log($message, $context, 'warning');
    }

    /**
     * Error log.
     *
     * @param string $message Message to log.
     * @param array  $context Log context.
     * @return void
     */
    public function error($message, $context = []) {
        $this->log($message, $context, 'error');
    }

    /**
     * Get logs.
     *
     * @param int    $limit  Number of logs to return.
     * @param int    $offset Offset.
     * @param string $level  Log level.
     * @return array
     */
    public function getLogs($limit = 50, $offset = 0, $level = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_api_logs';
        
        $query = "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query_args = [$limit, $offset];
        
        $results = $wpdb->get_results($wpdb->prepare($query, $query_args));
        
        return $results;
    }
}
