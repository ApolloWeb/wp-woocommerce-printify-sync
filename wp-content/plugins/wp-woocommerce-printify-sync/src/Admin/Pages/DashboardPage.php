<?php
/**
 * Dashboard Page
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Pages
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Services\Container;
use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;
use ApolloWeb\WPWooCommercePrintifySync\Services\TemplateService;

/**
 * Class DashboardPage
 *
 * Handles the dashboard admin page
 */
class DashboardPage
{
    /**
     * Service container
     *
     * @var Container
     */
    private Container $container;

    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Template service
     *
     * @var TemplateService
     */
    private TemplateService $template_service;

    /**
     * Constructor
     *
     * @param Container $container Service container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->logger = $container->get('logger');
        $this->template_service = $container->get('template');
    }

    /**
     * Render the dashboard page
     *
     * @return void
     */
    public function render(): void
    {
        // Check if API key is set
        $api_key = get_option('wpwps_api_key', '');
        $shop_id = get_option('wpwps_shop_id', '');
        
        if (empty($api_key) || empty($shop_id)) {
            $this->renderSetupPrompt();
            return;
        }
        
        // Get dashboard data
        $dashboard_data = $this->getDashboardData();
        
        // Render the dashboard
        $this->template_service->render('dashboard', $dashboard_data);
    }

    /**
     * Render setup prompt if API key is not set
     *
     * @return void
     */
    private function renderSetupPrompt(): void
    {
        $this->template_service->render('dashboard-setup-prompt', [
            'settings_url' => admin_url('admin.php?page=wpwps-settings'),
        ]);
    }

    /**
     * Get dashboard data
     *
     * @return array
     */
    private function getDashboardData(): array
    {
        global $wpdb;
        
        // Get product stats
        $product_stats = $this->getProductStats();
        
        // Get order stats
        $order_stats = $this->getOrderStats();
        
        // Get import queue stats
        $import_queue_stats = $this->getImportQueueStats();
        
        // Get email queue stats
        $email_queue = $this->container->get('email_queue');
        $email_queue_stats = $email_queue->getQueueStats();
        
        // Get API health
        $api_health = $this->getApiHealth();
        
        // Get recent logs
        $recent_logs = $this->getRecentLogs();
        
        // Get recent tickets
        $recent_tickets = $this->getRecentTickets();
        
        return [
            'product_stats' => $product_stats,
            'order_stats' => $order_stats,
            'import_queue_stats' => $import_queue_stats,
            'email_queue_stats' => $email_queue_stats,
            'api_health' => $api_health,
            'recent_logs' => $recent_logs,
            'recent_tickets' => $recent_tickets,
        ];
    }

    /**
     * Get product statistics
     *
     * @return array
     */
    private function getProductStats(): array
    {
        global $wpdb;
        
        $total_products = 0;
        $synced_products = 0;
        $out_of_sync_products = 0;
        $recently_synced = 0;
        
        // Get total Printify products
        $total_products = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' 
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'product' 
                AND post_status = 'publish'
            )"
        );
        
        // Get last synced time threshold (24 hours ago)
        $sync_threshold = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        // Get recently synced products
        $recently_synced = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                WHERE meta_key = '_printify_last_synced' 
                AND meta_value > %s
                AND post_id IN (
                    SELECT ID FROM {$wpdb->posts} 
                    WHERE post_type = 'product' 
                    AND post_status = 'publish'
                )",
                $sync_threshold
            )
        );
        
        // Calculate out of sync products
        $synced_products = $recently_synced;
        $out_of_sync_products = $total_products - $synced_products;
        
        if ($out_of_sync_products < 0) {
            $out_of_sync_products = 0;
        }
        
        return [
            'total' => (int) $total_products,
            'synced' => (int) $synced_products,
            'out_of_sync' => (int) $out_of_sync_products,
        ];
    }

    /**
     * Get order statistics
     *
     * @return array
     */
    private function getOrderStats(): array
    {
        // Get WooCommerce orders with Printify products
        $args = [
            'limit' => -1,
            'return' => 'ids',
            'meta_query' => [
                [
                    'key' => '_has_printify_products',
                    'value' => 'yes',
                    'compare' => '=',
                ],
            ],
        ];
        
        $order_ids = wc_get_orders($args);
        
        $total_orders = count($order_ids);
        $pending_orders = 0;
        $processing_orders = 0;
        $completed_orders = 0;
        
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                continue;
            }
            
            $status = $order->get_status();
            
            if ($status === 'pending') {
                $pending_orders++;
            } elseif (in_array($status, ['processing', 'on-hold'], true)) {
                $processing_orders++;
            } elseif ($status === 'completed') {
                $completed_orders++;
            }
        }
        
        return [
            'total' => $total_orders,
            'pending' => $pending_orders,
            'processing' => $processing_orders,
            'completed' => $completed_orders,
        ];
    }

    /**
     * Get import queue statistics
     *
     * @return array
     */
    private function getImportQueueStats(): array
    {
        // Check if Action Scheduler is available
        if (!function_exists('as_get_scheduled_actions')) {
            return [
                'total' => 0,
                'running' => 0,
                'pending' => 0,
                'completed' => 0,
                'failed' => 0,
            ];
        }
        
        // Get import product tasks
        $import_product_count = as_get_scheduled_actions([
            'hook' => 'wpwps_import_product',
            'status' => 'pending',
        ], 'count');
        
        // Get sync product tasks
        $sync_product_count = as_get_scheduled_actions([
            'hook' => 'wpwps_sync_product',
            'status' => 'pending',
        ], 'count');
        
        // Get running tasks
        $running_count = as_get_scheduled_actions([
            'hook' => ['wpwps_import_product', 'wpwps_sync_product'],
            'status' => 'running',
        ], 'count');
        
        // Get completed tasks (last 24 hours)
        $completed_count = as_get_scheduled_actions([
            'hook' => ['wpwps_import_product', 'wpwps_sync_product'],
            'status' => 'complete',
            'date' => [
                'after' => (time() - DAY_IN_SECONDS),
            ],
        ], 'count');
        
        // Get failed tasks (last 24 hours)
        $failed_count = as_get_scheduled_actions([
            'hook' => ['wpwps_import_product', 'wpwps_sync_product'],
            'status' => 'failed',
            'date' => [
                'after' => (time() - DAY_IN_SECONDS),
            ],
        ], 'count');
        
        return [
            'total' => $import_product_count + $sync_product_count,
            'running' => $running_count,
            'pending' => $import_product_count + $sync_product_count,
            'completed' => $completed_count,
            'failed' => $failed_count,
        ];
    }

    /**
     * Get API health status
     *
     * @return array
     */
    private function getApiHealth(): array
    {
        // Get last API check timestamp
        $last_api_check = get_option('wpwps_last_api_check', 0);
        $api_status = get_option('wpwps_api_status', 'unknown');
        
        // Check if we need to refresh the status (every hour)
        if (time() - $last_api_check > HOUR_IN_SECONDS) {
            // Test the API connection
            $api_service = $this->container->get('api');
            $result = $api_service->testConnection();
            
            $api_status = $result['success'] ? 'healthy' : 'error';
            
            // Update status and timestamp
            update_option('wpwps_api_status', $api_status);
            update_option('wpwps_last_api_check', time());
        }
        
        // Get webhook status
        $webhook_status = get_option('wpwps_webhook_status', 'unknown');
        $last_webhook_received = get_option('wpwps_last_webhook_received', 0);
        
        // If we haven't received a webhook in 24 hours, mark as potentially unhealthy
        if (time() - $last_webhook_received > DAY_IN_SECONDS) {
            $webhook_status = 'warning';
        }
        
        return [
            'api_status' => $api_status,
            'webhook_status' => $webhook_status,
            'last_api_check' => $last_api_check,
            'last_webhook_received' => $last_webhook_received,
        ];
    }

    /**
     * Get recent logs
     *
     * @param int $limit Number of logs to retrieve
     * @return array
     */
    private function getRecentLogs(int $limit = 10): array
    {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'wpwps_sync_logs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$logs_table}'");
        
        if (!$table_exists) {
            return [];
        }
        
        // Get recent logs
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$logs_table} 
                ORDER BY created_at DESC 
                LIMIT %d",
                $limit
            )
        );
        
        return $logs ? $logs : [];
    }

    /**
     * Get recent support tickets
     *
     * @param int $limit Number of tickets to retrieve
     * @return array
     */
    private function getRecentTickets(int $limit = 5): array
    {
        global $wpdb;
        
        $tickets_table = $wpdb->prefix . 'wpwps_tickets';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$tickets_table}'");
        
        if (!$table_exists) {
            return [];
        }
        
        // Get recent tickets
        $tickets = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, email, subject, status, order_id, created_at, updated_at 
                FROM {$tickets_table} 
                WHERE status = 'open' 
                ORDER BY created_at DESC 
                LIMIT %d",
                $limit
            )
        );
        
        return $tickets ? $tickets : [];
    }
}
