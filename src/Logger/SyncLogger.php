<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Logger;

/**
 * Logger implementation for the plugin
 */
class SyncLogger implements LoggerInterface {
    /**
     * @var string The log table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpwps_logs';
        
        // Create log table if it doesn't exist
        $this->maybe_create_table();
    }
    
    /**
     * Create the log table if it doesn't exist
     */
    private function maybe_create_table() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->table_name
        )) === $this->table_name;
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$this->table_name} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                level varchar(20) NOT NULL,
                action varchar(50) NOT NULL,
                message text NOT NULL,
                context longtext,
                PRIMARY KEY (id),
                KEY level (level),
                KEY action (action),
                KEY timestamp (timestamp)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Log an informational message
     *
     * @param string $action   The action being performed
     * @param string $message  The message to log
     * @param array  $context  Additional context data
     * @return void
     */
    public function log_info($action, $message, array $context = []) {
        $this->log('info', $action, $message, $context);
    }
    
    /**
     * Log an error message
     *
     * @param string $action   The action being performed
     * @param string $message  The error message
     * @param array  $context  Additional context data
     * @return void
     */
    public function log_error($action, $message, array $context = []) {
        $this->log('error', $action, $message, $context);
    }
    
    /**
     * Log a success message
     *
     * @param string $action   The action being performed
     * @param string $message  The success message
     * @param array  $context  Additional context data
     * @return void
     */
    public function log_success($action, $message, array $context = []) {
        $this->log('success', $action, $message, $context);
    }
    
    /**
     * Log a warning message
     *
     * @param string $action   The action being performed
     * @param string $message  The warning message
     * @param array  $context  Additional context data
     * @return void
     */
    public function log_warning($action, $message, array $context = []) {
        $this->log('warning', $action, $message, $context);
    }
    
    /**
     * Get logs by criteria
     *
     * @param array $args Query arguments
     * @return array Log entries
     */
    public function get_logs(array $args = []) {
        global $wpdb;
        
        $defaults = [
            'level' => '',
            'action' => '',
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'timestamp',
            'order' => 'DESC',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = [];
        $values = [];
        
        if (!empty($args['level'])) {
            $where[] = 'level = %s';
            $values[] = $args['level'];
        }
        
        if (!empty($args['action'])) {
            $where[] = 'action = %s';
            $values[] = $args['action'];
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $orderby = sanitize_sql_orderby($args['orderby'] ?: 'timestamp');
        if (!$orderby) {
            $orderby = 'timestamp';
        }
        
        $limit = (int)$args['limit'];
        $offset = (int)$args['offset'];
        
        $query = "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        
        // Add parameters for LIMIT and OFFSET
        $values[] = $limit;
        $values[] = $offset;
        
        // Prepare the final query
        $prepared_query = $wpdb->prepare($query, $values);
        
        // Execute query and return results
        return $wpdb->get_results($prepared_query, ARRAY_A);
    }
    
    /**
     * Log a message with the specified level
     *
     * @param string $level    The log level
     * @param string $action   The action being performed
     * @param string $message  The message to log
     * @param array  $context  Additional context data
     */
    private function log($level, $action, $message, array $context = []) {
        global $wpdb;
        
        // Don't log if WP_DEBUG is not enabled and this is not an error
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            if ($level !== 'error') {
                return;
            }
        }
        
        $data = [
            'level' => $level,
            'action' => substr($action, 0, 50),  // Truncate to match column length
            'message' => $message,
            'context' => !empty($context) ? wp_json_encode($context) : null
        ];
        
        $wpdb->insert($this->table_name, $data);
        
        // If this is an error, also log to error_log for immediate visibility
        if ($level === 'error') {
            error_log(sprintf(
                '[WPWPS-ERROR] [%s] %s: %s',
                $action,
                $message,
                !empty($context) ? wp_json_encode($context) : ''
            ));
        }
    }
    
    /**
     * Purge old logs
     *
     * @param int $days Number of days to keep logs (default: 30)
     * @return int Number of logs deleted
     */
    public function purge_old_logs($days = 30) {
        global $wpdb;
        
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $query = $wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE timestamp < %s",
            $date
        );
        
        $wpdb->query($query);
        
        return $wpdb->rows_affected;
    }
    
    /**
     * Log a debug message (for compatibility with rate limiter)
     *
     * @param string $action   The action being performed
     * @param string $message  The message to log
     * @param array  $context  Additional context data
     * @return void
     */
    public function log_debug($action, $message, array $context = []) {
        // Only log in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log('debug', $action, $message, $context);
        }
    }
}
