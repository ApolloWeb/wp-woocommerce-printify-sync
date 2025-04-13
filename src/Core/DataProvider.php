<?php
/**
 * Data Provider
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Provides data for the plugin
 */
class DataProvider {
    /**
     * Get sync history
     * 
     * @param int $days Number of days to retrieve
     * @return array Sync history
     */
    private function getSyncHistory(int $days = 7): array {
        global $wpdb;
        
        // Check if we have a sync_history table
        $table_name = $wpdb->prefix . 'wpwps_sync_history';
        $history = [];
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            // Get real data from database
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DATE(start_time) as date, 
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    AVG(TIMESTAMPDIFF(SECOND, start_time, end_time)) as duration
                    FROM $table_name 
                    WHERE start_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
                    GROUP BY DATE(start_time)
                    ORDER BY date DESC",
                    $days
                ),
                ARRAY_A
            );
            
            if ($results) {
                $history = $results;
            }
        }
        
        // Fill in missing days or return sample data if no real data exists
        if (empty($history)) {
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $history[] = [
                    'date' => $date,
                    'successful' => rand(5, 20),
                    'failed' => rand(0, 3),
                    'duration' => rand(30, 120)
                ];
            }
        }
        
        return $history;
    }

    /**
     * Get product performance stats
     * 
     * @return array Performance stats
     */
    public function getProductPerformanceStats(): array {
        // In a real implementation, this would calculate from real order and product data
        return [
            'top_sellers' => [
                ['id' => 1, 'name' => 'Vintage T-Shirt', 'sales' => 50],
                ['id' => 3, 'name' => 'Canvas Tote Bag', 'sales' => 43],
                ['id' => 2, 'name' => 'Premium Hoodie', 'sales' => 20]
            ],
            'highest_revenue' => [
                ['id' => 1, 'name' => 'Vintage T-Shirt', 'revenue' => 1249.50],
                ['id' => 3, 'name' => 'Canvas Tote Bag', 'revenue' => 1019.49],
                ['id' => 2, 'name' => 'Premium Hoodie', 'revenue' => 799.80]
            ],
            'highest_margin' => [
                ['id' => 3, 'name' => 'Canvas Tote Bag', 'margin' => 45],
                ['id' => 1, 'name' => 'Vintage T-Shirt', 'margin' => 42],
                ['id' => 2, 'name' => 'Premium Hoodie', 'margin' => 38]
            ],
            'conversion_rates' => [
                ['id' => 3, 'name' => 'Canvas Tote Bag', 'rate' => 5.1],
                ['id' => 1, 'name' => 'Vintage T-Shirt', 'rate' => 4.8],
                ['id' => 2, 'name' => 'Premium Hoodie', 'rate' => 3.2]
            ]
        ];
    }

    /**
     * Get sales by product
     * 
     * @return array Sales by product
     */
    public function getSalesByProduct(): array {
        // Sample data for visualization
        return [
            [
                'product' => 'Vintage T-Shirt',
                'data' => [15, 12, 10, 14, 18, 16, 19]
            ],
            [
                'product' => 'Canvas Tote Bag',
                'data' => [8, 10, 12, 15, 13, 12, 16]
            ],
            [
                'product' => 'Premium Hoodie',
                'data' => [5, 4, 7, 6, 8, 10, 9]
            ]
        ];
    }

    /**
     * Get orders grouped by status
     * 
     * @return array Orders grouped by status
     */
    public function getOrdersGroupedByStatus(): array {
        if (function_exists('wc_get_order_statuses')) {
            $statuses = wc_get_order_statuses();
            $result = [];
            
            foreach ($statuses as $status_key => $status_label) {
                $clean_key = str_replace('wc-', '', $status_key);
                $count = $this->getOrderCountByStatus($clean_key);
                
                $result[$clean_key] = [
                    'label' => $status_label,
                    'count' => $count
                ];
            }
            
            return $result;
        }
        
        // Sample data if WooCommerce functions are not available
        return [
            'pending' => [
                'label' => __('Pending', 'wp-woocommerce-printify-sync'),
                'count' => 3
            ],
            'processing' => [
                'label' => __('Processing', 'wp-woocommerce-printify-sync'),
                'count' => 8
            ],
            'on-hold' => [
                'label' => __('On Hold', 'wp-woocommerce-printify-sync'),
                'count' => 2
            ],
            'completed' => [
                'label' => __('Completed', 'wp-woocommerce-printify-sync'),
                'count' => 45
            ]
        ];
    }

    /**
     * Get order count by status
     * 
     * @param string $status Order status
     * @return int Order count
     */
    private function getOrderCountByStatus(string $status): int {
        if (function_exists('wc_get_orders')) {
            $args = [
                'status' => $status,
                'limit' => -1,
                'return' => 'ids',
            ];
            
            $orders = wc_get_orders($args);
            return count($orders);
        }
        
        // Sample counts if WooCommerce functions are not available
        $counts = [
            'pending' => 3,
            'processing' => 8,
            'on-hold' => 2,
            'completed' => 45,
            'cancelled' => 1,
            'refunded' => 0
        ];
        
        return $counts[$status] ?? 0;
    }

    /**
     * Get unfulfilled order count
     * 
     * @return int Unfulfilled order count
     */
    public function getUnfulfilledOrderCount(): int {
        if (function_exists('wc_get_orders')) {
            $args = [
                'status' => ['processing', 'on-hold'],
                'limit' => -1,
                'return' => 'ids',
            ];
            
            $orders = wc_get_orders($args);
            return count($orders);
        }
        
        // Sample count if WooCommerce functions are not available
        return 10;
    }

    /**
     * Get recent shipments
     * 
     * @return array Recent shipments
     */
    public function getRecentShipments(): array {
        // Sample data - in real implementation, this would query order shipment data
        return [
            [
                'order_id' => '1023',
                'tracking' => 'USPS123456789',
                'carrier' => 'USPS',
                'date' => date('Y-m-d', strtotime('-2 days')),
                'status' => 'delivered',
                'customer' => 'John Doe'
            ],
            [
                'order_id' => '1019',
                'tracking' => 'FEDEX987654321',
                'carrier' => 'FedEx',
                'date' => date('Y-m-d', strtotime('-3 days')),
                'status' => 'in_transit',
                'customer' => 'Jane Smith'
            ],
            [
                'order_id' => '1018',
                'tracking' => 'UPS12345678',
                'carrier' => 'UPS',
                'date' => date('Y-m-d', strtotime('-5 days')),
                'status' => 'delivered',
                'customer' => 'Mike Johnson'
            ]
        ];
    }

    /**
     * Get order trends data
     * 
     * @return array Order trends
     */
    public function getOrderTrends(): array {
        // Sample data for visualization
        $days = 7;
        $labels = [];
        $orders_data = [];
        $revenue_data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('M j', strtotime("-$i days"));
            $labels[] = $date;
            $orders_data[] = mt_rand(1, 10);
            $revenue_data[] = mt_rand(100, 500);
        }
        
        return [
            'labels' => $labels,
            'orders' => $orders_data,
            'revenue' => $revenue_data
        ];
    }

    /**
     * Get customer insights
     * 
     * @return array Customer insights
     */
    public function getCustomerInsights(): array {
        // Sample data - in real implementation, this would calculate from real order data
        return [
            'total' => 86,
            'returning' => 34,
            'new' => 52,
            'average_order' => 53.42,
            'top_countries' => [
                ['country' => 'United States', 'count' => 45],
                ['country' => 'Canada', 'count' => 12],
                ['country' => 'United Kingdom', 'count' => 8]
            ],
            'customer_satisfaction' => 4.7
        ];
    }

    /**
     * Get count of pending orders that need attention
     * This is called from MenuManager's getSubmenus() method
     * 
     * @return int Count of pending orders
     */
    public function getPendingOrderCount(): int {
        // Get real pending orders from WooCommerce
        $pendingOrders = $this->getOrdersByStatus('pending');
        $urgentOrders = $this->getUrgentOrders();
            
        // Create notification data for use in the dashboard
        update_option('wpwps_pending_orders_data', [
            'count' => count($pendingOrders),
            'urgent' => count($urgentOrders),
            'last_checked' => current_time('mysql')
        ]);

        // Return total count for badge display
        return count($pendingOrders) + count($urgentOrders);
    }

    /**
     * Get orders by specific status
     *
     * @param string $status Order status to fetch
     * @return array Orders with specified status
     */
    public function getOrdersByStatus(string $status): array {
        if (!function_exists('wc_get_orders')) {
            // Return sample data if WooCommerce is not active
            $counts = [
                'pending' => 3,
                'processing' => 8,
                'on-hold' => 2,
                'completed' => 45,
            ];
            
            return array_fill(0, $counts[$status] ?? 0, 'order');
        }
        
        $args = [
            'status' => $status,
            'limit' => -1,
            'return' => 'ids',
        ];
        
        $orders = wc_get_orders($args);
        
        return $orders;
    }

    /**
     * Get urgent orders requiring immediate attention
     *
     * @return array Urgent orders
     */
    public function getUrgentOrders(): array {
        // In a real implementation, this would find orders with special flags
        // For now, return a placeholder
        return array_fill(0, 2, 'urgent_order');
    }
}