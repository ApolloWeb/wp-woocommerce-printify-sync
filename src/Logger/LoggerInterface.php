<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Logger;

/**
 * Logger Interface
 */
interface LoggerInterface {
    /**
     * Log an informational message
     *
     * @param string $action   The action being performed
     * @param string $message  The message to log
     * @param array  $context  Additional context data
     * @return void
     */
    public function log_info($action, $message, array $context = []);
    
    /**
     * Log an error message
     *
     * @param string $action   The action being performed
     * @param string $message  The error message
     * @param array  $context  Additional context data
     * @return void
     */
    public function log_error($action, $message, array $context = []);
    
    /**
     * Log a success message
     *
     * @param string $action   The action being performed
     * @param string $message  The success message
     * @param array  $context  Additional context data
     * @return void
     */
    public function log_success($action, $message, array $context = []);
    
    /**
     * Log a warning message
     *
     * @param string $action   The action being performed
     * @param string $message  The warning message
     * @param array  $context  Additional context data
     * @return void
     */
    public function log_warning($action, $message, array $context = []);
    
    /**
     * Get logs by criteria
     *
     * @param array $args Query arguments
     * @return array Log entries
     */
    public function get_logs(array $args = []);
}
