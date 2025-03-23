<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Controllers;

class AdminDashboardController {
    private $templating;
    private $logger;
    
    public function __construct(BladeEngine $templating, Logger $logger) {
        $this->templating = $templating;
        $this->logger = $logger;
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_get_dashboard_stats', [$this, 'getDashboardStats']);
    }
    
    public function render(): void {
        $data = [
            'stats' => $this->getStats(),
            'recent_activity' => $this->getRecentActivity(),
            'sync_stats' => $this->getSyncStats()
        ];
        
        echo $this->templating->render('admin/dashboard', $data);
    }
    
    public function getDashboardStats(): void {
        check_ajax_referer('wpps_admin');
        
        try {
            wp_send_json_success([
                'stats' => $this->getStats(),
                'chart_data' => $this->getChartData(),
                'recent_activity' => $this->getRecentActivity()
            ]);
        } catch (\Exception $e) {
            $this->logger->log("Error fetching dashboard stats: " . $e->getMessage(), 'error');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    private function getStats(): array {
        return [
            'products' => $this->getProductCount(),
            'orders' => $this->getOrderCount(),
            'pending_sync' => $this->getPendingSyncCount(),
            'revenue' => $this->getRevenueStats()
        ];
    }
    
    private function getProductCount(): int {
        $products = wc_get_products([
            'limit' => -1,
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        return count($products);
    }
    
    private function getOrderCount(): int {
        return wc_order_search([
            'limit' => -1,
            'meta_query' => [
                [
                    'key' => '_printify_linked',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ])->total;
    }
    
    private function getPendingSyncCount(): int {
        return wc_order_search([
            'limit' => -1,
            'meta_query' => [
                [
                    'key' => '_printify_linked',
                    'value' => '1',
                    'compare' => '='
                ],
                [
                    'key' => '_printify_sync_status',
                    'value' => 'pending',
                    'compare' => '='
                ]
            ]
        ])->total;
    }
    
    private function getRevenueStats(): array {
        // Get revenue data - implementation depends on business needs
        return [
            'total' => wc_price(0),
            'this_month' => wc_price(0),
            'trend' => '+0%'
        ];
    }
    
    private function getChartData(): array {
        // Generate chart data for orders and revenue
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'orders' => [12, 19, 3, 5, 2, 3],
            'revenue' => [1200, 1900, 300, 500, 200, 300]
        ];
    }
    
    private function getRecentActivity(): array {
        global $wpdb;
        
        // Fetch recent activities from database
        // This would be populated with actual data in a real implementation
        return [
            [
                'type' => 'order_synced',
                'message' => 'Order #1234 synced successfully',
                'time' => '2 minutes ago',
                'status' => 'success'
            ],
            [
                'type' => 'product_sync',
                'message' => '15 products updated',
                'time' => '1 hour ago',
                'status' => 'warning'
            ]
        ];
    }
    
    private function getSyncStats(): array {
        return [
            'last_sync' => get_option('wpwps_last_sync_time', ''),
            'next_sync' => wp_next_scheduled('wpwps_sync_products'),
            'success_rate' => $this->calculateSyncSuccessRate()
        ];
    }
    
    private function calculateSyncSuccessRate(): string {
        $total = (int)get_option('wpwps_total_sync_attempts', 0);
        $successful = (int)get_option('wpwps_successful_syncs', 0);
        
        if ($total === 0) {
            return '0%';
        }
        
        return round(($successful / $total) * 100) . '%';
    }
}
