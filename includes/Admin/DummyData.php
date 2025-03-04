<?php
/**
 * Dummy Data Generator
 *
 * @package WP_WooCommerce_Printify_Sync
 */

namespace WpWoocommercePrintifySync\Admin;

defined('ABSPATH') || exit;

class DummyData {
    /**
     * Get dummy sales data for charts
     */
    public static function get_sales_data() {
        return [
            'labels' => [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ],
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => [65, 59, 80, 81, 56, 55, 40, 88, 58, 72, 74, 79],
                    'borderColor' => '#6777ef',
                    'backgroundColor' => 'rgba(103, 119, 239, 0.2)'
                ],
                [
                    'label' => 'Orders',
                    'data' => [28, 48, 40, 19, 86, 27, 90, 42, 64, 43, 52, 44],
                    'borderColor' => '#ffa426',
                    'backgroundColor' => 'rgba(255, 164, 38, 0.2)'
                ]
            ]
        ];
    }

    /**
     * Get dummy product sync data
     */
    public static function get_sync_stats() {
        return [
            'total_products' => 245,
            'synced_today' => 12,
            'failed_syncs' => 3,
            'pending_syncs' => 8,
            'last_sync' => '2025-03-04 13:15:22'
        ];
    }

    /**
     * Get dummy recent orders
     */
    public static function get_recent_orders() {
        return [
            [
                'order_id' => '#WC-1234',
                'status' => 'processing',
                'customer' => 'John Doe',
                'total' => 129.99,
                'date' => '2025-03-04 12:45:33'
            ],
            [
                'order_id' => '#WC-1233',
                'status' => 'completed',
                'customer' => 'Jane Smith',
                'total' => 89.99,
                'date' => '2025-03-04 11:30:21'
            ],