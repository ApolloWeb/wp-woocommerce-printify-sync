<?php
/**
 * Logs Provider
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Providers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

/**
 * Logs Provider class
 */
class LogsProvider extends ServiceProvider
{
    /**
     * Log table name
     * 
     * @var string
     */
    protected $table;
    
    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'wpwps_logs';
        
        // Register activation hook to create logs table
        register_activation_hook(WPWPS_PLUGIN_FILE, [$this, 'createLogsTable']);
        
        // Register log handlers
        add_action('wpwps_log', [$this, 'addLog'], 10, 3);
        
        // Register API request logging
        add_action('wpwps_api_request', [$this, 'logApiRequest'], 10, 4);
        add_action('wpwps_api_response', [$this, 'logApiResponse'], 10, 5);
        
        // Register product sync logging
        add_action('wpwps_product_sync_started', [$this, 'logProductSyncStarted'], 10, 2);
        add_action('wpwps_product_sync_completed', [$this, 'logProductSyncCompleted'], 10, 3);
        add_action('wpwps_product_sync_error', [$this, 'logProductSyncError'], 10, 3);
        
        // Register order sync logging
        add_action('wpwps_order_sync_started', [$this, 'logOrderSyncStarted'], 10, 2);
        add_action('wpwps_order_sync_completed', [$this, 'logOrderSyncCompleted'], 10, 3);
        add_action('wpwps_order_sync_error', [$this, 'logOrderSyncError'], 10, 3);
        
        // Register webhook logging
        add_action('wpwps_webhook_received', [$this, 'logWebhookReceived'], 10, 2);
        add_action('wpwps_webhook_processed', [$this, 'logWebhookProcessed'], 10, 3);
        add_action('wpwps_webhook_error', [$this, 'logWebhookError'], 10, 3);
    }
    
    /**
     * Create logs table
     *
     * @return void
     */
    public function createLogsTable()
    {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp int(11) NOT NULL,
            type varchar(50) NOT NULL,
            message text NOT NULL,
            details longtext,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    /**
     * Add log entry
     *
     * @param string $type    Log type (api, sync, webhook, error)
     * @param string $message Log message
     * @param array  $details Optional. Additional details
     * @return int|false Log ID or false on failure
     */
    public function addLog($type, $message, $details = [])
    {
        global $wpdb;
        
        $data = [
            'timestamp' => time(),
            'type' => sanitize_text_field($type),
            'message' => sanitize_text_field($message),
            'details' => !empty($details) ? wp_json_encode($details) : null,
        ];
        
        $result = $wpdb->insert($this->table, $data, ['%d', '%s', '%s', '%s']);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get logs
     *
     * @param array $args Query arguments
     * @return array Logs data and pagination
     */
    public function getLogs($args = [])
    {
        global $wpdb;
        
        $defaults = [
            'type' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'timestamp',
            'order' => 'DESC',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Build query
        $sql = "SELECT * FROM {$this->table}";
        $countSql = "SELECT COUNT(*) FROM {$this->table}";
        
        // Add filters
        $where = [];
        $whereValues = [];
        
        if (!empty($args['type'])) {
            $where[] = 'type = %s';
            $whereValues[] = $args['type'];
        }
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
            $countSql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        // Add order
        $sql .= $wpdb->prepare(' ORDER BY %s %s', $args['orderby'], $args['order']);
        
        // Get total count
        $total = $wpdb->get_var($wpdb->prepare($countSql, $whereValues));
        
        // Add pagination
        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql .= $wpdb->prepare(' LIMIT %d, %d', $offset, $args['per_page']);
        
        // Get logs
        $results = $wpdb->get_results($wpdb->prepare($sql, $whereValues), ARRAY_A);
        
        // Process logs
        $logs = [];
        foreach ($results as $row) {
            $logs[] = [
                'id' => $row['id'],
                'timestamp' => $row['timestamp'],
                'type' => $row['type'],
                'message' => $row['message'],
                'details' => !empty($row['details']) ? json_decode($row['details'], true) : null,
            ];
        }
        
        // Build pagination
        $totalPages = ceil($total / $args['per_page']);
        
        $pagination = [
            'total' => (int) $total,
            'per_page' => $args['per_page'],
            'current_page' => $args['page'],
            'total_pages' => $totalPages,
            'start' => $offset + 1,
            'end' => min($offset + $args['per_page'], $total),
        ];
        
        return [
            'logs' => $logs,
            'pagination' => $pagination,
        ];
    }
    
    /**
     * Get log statistics
     *
     * @return array Log statistics
     */
    public function getLogStats()
    {
        global $wpdb;
        
        $stats = [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}"),
            'api' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE type = 'api'"),
            'sync' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE type = 'sync'"),
            'webhook' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE type = 'webhook'"),
            'error' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE type = 'error'"),
        ];
        
        return $stats;
    }
    
    /**
     * Log API request
     *
     * @param string $method  HTTP method
     * @param string $path    API path
     * @param array  $options Request options
     * @param int    $retry   Retry count
     * @return void
     */
    public function logApiRequest($method, $path, $options, $retry)
    {
        // Remove sensitive data
        if (isset($options['headers']['Authorization'])) {
            $options['headers']['Authorization'] = 'Bearer [REDACTED]';
        }
        
        $this->addLog('api', "API Request: {$method} {$path}", [
            'method' => $method,
            'path' => $path,
            'options' => $options,
            'retry' => $retry,
        ]);
    }
    
    /**
     * Log API response
     *
     * @param string $method   HTTP method
     * @param string $path     API path
     * @param array  $options  Request options
     * @param int    $code     Response code
     * @param array  $response Response data
     * @return void
     */
    public function logApiResponse($method, $path, $options, $code, $response)
    {
        // Remove sensitive data
        if (isset($options['headers']['Authorization'])) {
            $options['headers']['Authorization'] = 'Bearer [REDACTED]';
        }
        
        $message = "API Response: {$method} {$path} - {$code}";
        
        // Log errors as error type
        if ($code < 200 || $code >= 300) {
            $this->addLog('error', $message, [
                'method' => $method,
                'path' => $path,
                'options' => $options,
                'code' => $code,
                'response' => $response,
            ]);
        } else {
            $this->addLog('api', $message, [
                'method' => $method,
                'path' => $path,
                'options' => $options,
                'code' => $code,
                'response' => $response,
            ]);
        }
    }
    
    /**
     * Log product sync started
     *
     * @param int   $productId WooCommerce product ID
     * @param array $data      Sync data
     * @return void
     */
    public function logProductSyncStarted($productId, $data)
    {
        $this->addLog('sync', "Product sync started: #{$productId}", [
            'product_id' => $productId,
            'data' => $data,
        ]);
    }
    
    /**
     * Log product sync completed
     *
     * @param int    $productId    WooCommerce product ID
     * @param string $printifyId   Printify product ID
     * @param array  $responseData Response data
     * @return void
     */
    public function logProductSyncCompleted($productId, $printifyId, $responseData)
    {
        $this->addLog('sync', "Product sync completed: #{$productId} -> {$printifyId}", [
            'product_id' => $productId,
            'printify_id' => $printifyId,
            'response' => $responseData,
        ]);
    }
    
    /**
     * Log product sync error
     *
     * @param int    $productId WooCommerce product ID
     * @param string $error     Error message
     * @param array  $details   Error details
     * @return void
     */
    public function logProductSyncError($productId, $error, $details = [])
    {
        $this->addLog('error', "Product sync error: #{$productId} - {$error}", [
            'product_id' => $productId,
            'error' => $error,
            'details' => $details,
        ]);
    }
    
    /**
     * Log order sync started
     *
     * @param int   $orderId WooCommerce order ID
     * @param array $data    Sync data
     * @return void
     */
    public function logOrderSyncStarted($orderId, $data)
    {
        $this->addLog('sync', "Order sync started: #{$orderId}", [
            'order_id' => $orderId,
            'data' => $data,
        ]);
    }
    
    /**
     * Log order sync completed
     *
     * @param int    $orderId     WooCommerce order ID
     * @param string $printifyId  Printify order ID
     * @param array  $responseData Response data
     * @return void
     */
    public function logOrderSyncCompleted($orderId, $printifyId, $responseData)
    {
        $this->addLog('sync', "Order sync completed: #{$orderId} -> {$printifyId}", [
            'order_id' => $orderId,
            'printify_id' => $printifyId,
            'response' => $responseData,
        ]);
    }
    
    /**
     * Log order sync error
     *
     * @param int    $orderId WooCommerce order ID
     * @param string $error   Error message
     * @param array  $details Error details
     * @return void
     */
    public function logOrderSyncError($orderId, $error, $details = [])
    {
        $this->addLog('error', "Order sync error: #{$orderId} - {$error}", [
            'order_id' => $orderId,
            'error' => $error,
            'details' => $details,
        ]);
    }
    
    /**
     * Log webhook received
     *
     * @param string $topic Webhook topic
     * @param array  $data  Webhook data
     * @return void
     */
    public function logWebhookReceived($topic, $data)
    {
        $this->addLog('webhook', "Webhook received: {$topic}", [
            'topic' => $topic,
            'data' => $data,
        ]);
    }
    
    /**
     * Log webhook processed
     *
     * @param string $topic    Webhook topic
     * @param array  $data     Webhook data
     * @param mixed  $response Processing response
     * @return void
     */
    public function logWebhookProcessed($topic, $data, $response)
    {
        $this->addLog('webhook', "Webhook processed: {$topic}", [
            'topic' => $topic,
            'data' => $data,
            'response' => $response,
        ]);
    }
    
    /**
     * Log webhook error
     *
     * @param string $topic  Webhook topic
     * @param string $error  Error message
     * @param mixed  $data   Webhook data
     * @return void
     */
    public function logWebhookError($topic, $error, $data = null)
    {
        $this->addLog('error', "Webhook error: {$topic} - {$error}", [
            'topic' => $topic,
            'error' => $error,
            'data' => $data,
        ]);
    }
    
    /**
     * Render the logs page
     *
     * @return void
     */
    public function renderPage()
    {
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        
        $result = $this->getLogs([
            'type' => $type,
            'page' => $page,
        ]);
        
        $data = [
            'logs' => $result['logs'],
            'pagination' => $result['pagination'],
            'current_type' => $type,
            'stats' => $this->getLogStats(),
        ];
        
        View::render('wpwps-logs', $data);
    }
}