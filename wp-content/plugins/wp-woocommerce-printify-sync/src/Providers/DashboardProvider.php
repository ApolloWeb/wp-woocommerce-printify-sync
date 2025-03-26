<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DashboardProvider extends ServiceProvider
{
    private const OPTION_PREFIX = 'wpwps_';
    private Client $client;

    public function boot(): void
    {
        $this->client = new Client([
            'base_uri' => 'https://api.printify.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getApiKey(),
                'Accept' => 'application/json',
            ]
        ]);

        $this->registerAdminMenu(
            'WC Printify Dashboard',
            'WC Printify',
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboardPage']
        );

        $this->registerAjaxEndpoint('wpwps_get_dashboard_stats', [$this, 'getDashboardStats']);
        $this->registerAjaxEndpoint('wpwps_get_chart_data', [$this, 'getChartData']);
    }

    public function renderDashboardPage(): void
    {
        $data = [
            'stats' => $this->getStats(),
            'recent_orders' => $this->getRecentOrders(),
            'sync_status' => $this->getSyncStatus(),
            'alerts' => $this->getAlerts()
        ];

        echo $this->view->render('wpwps-dashboard', $data);
    }

    public function getDashboardStats(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        wp_send_json_success([
            'stats' => $this->getStats(),
            'alerts' => $this->getAlerts()
        ]);
    }

    public function getChartData(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        $period = $_GET['period'] ?? '7days';
        wp_send_json_success([
            'chart' => $this->generateChartData($period)
        ]);
    }

    private function getStats(): array
    {
        global $wpdb;

        $stats = [
            'total_products' => 0,
            'synced_products' => 0,
            'total_orders' => 0,
            'pending_orders' => 0,
            'revenue' => [
                'today' => 0,
                'week' => 0,
                'month' => 0
            ]
        ];

        // Get product stats
        $stats['total_products'] = $this->getTotalProducts();
        $stats['synced_products'] = $this->getSyncedProductsCount();

        // Get order stats
        $stats['total_orders'] = $this->getTotalOrders();
        $stats['pending_orders'] = $this->getPendingOrdersCount();

        // Get revenue stats
        $stats['revenue'] = $this->getRevenueStats();

        return $stats;
    }

    private function getRecentOrders(int $limit = 5): array
    {
        $orders = wc_get_orders([
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => '_printify_order_id',
            'meta_compare' => 'EXISTS'
        ]);

        return array_map(function($order) {
            return [
                'id' => $order->get_id(),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'printify_id' => get_post_meta($order->get_id(), '_printify_order_id', true)
            ];
        }, $orders);
    }

    private function getSyncStatus(): array
    {
        return [
            'last_sync' => get_option(self::OPTION_PREFIX . 'last_sync'),
            'next_sync' => wp_next_scheduled('wpwps_scheduled_sync'),
            'sync_frequency' => get_option(self::OPTION_PREFIX . 'sync_frequency', 'daily'),
            'auto_sync' => get_option(self::OPTION_PREFIX . 'auto_sync', true)
        ];
    }

    private function getAlerts(): array
    {
        $alerts = [];

        // Check API connection
        if (!$this->getApiKey()) {
            $alerts[] = [
                'type' => 'error',
                'message' => 'API key not configured. Please set up your Printify API credentials.',
                'action' => admin_url('admin.php?page=wpwps-settings')
            ];
        }

        // Check sync status
        $lastSync = get_option(self::OPTION_PREFIX . 'last_sync');
        if (!$lastSync || strtotime($lastSync) < strtotime('-24 hours')) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Products haven\'t been synchronized in the last 24 hours.',
                'action' => admin_url('admin.php?page=wpwps-products')
            ];
        }

        // Check failed orders
        $failedOrders = $this->getFailedOrdersCount();
        if ($failedOrders > 0) {
            $alerts[] = [
                'type' => 'error',
                'message' => "There are {$failedOrders} failed order synchronizations.",
                'action' => admin_url('admin.php?page=wpwps-orders')
            ];
        }

        return $alerts;
    }

    private function generateChartData(string $period): array
    {
        global $wpdb;

        $data = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => [],
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)'
                ],
                [
                    'label' => 'Revenue',
                    'data' => [],
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)'
                ]
            ]
        ];

        $range = $this->getDateRange($period);
        $current = $range['start'];

        while ($current <= $range['end']) {
            $data['labels'][] = $current->format('Y-m-d');

            // Get orders count
            $ordersCount = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE post_type = 'shop_order' 
                AND post_status != 'trash'
                AND post_date LIKE %s",
                $current->format('Y-m-d') . '%'
            ));

            // Get revenue
            $revenue = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(meta_value) FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE p.post_type = 'shop_order'
                AND p.post_status != 'trash'
                AND pm.meta_key = '_order_total'
                AND p.post_date LIKE %s",
                $current->format('Y-m-d') . '%'
            ));

            $data['datasets'][0]['data'][] = (int) $ordersCount;
            $data['datasets'][1]['data'][] = (float) $revenue;

            $current->modify('+1 day');
        }

        return $data;
    }

    private function getDateRange(string $period): array
    {
        $end = new \DateTime();
        $start = new \DateTime();

        switch ($period) {
            case '7days':
                $start->modify('-6 days');
                break;
            case '30days':
                $start->modify('-29 days');
                break;
            case '90days':
                $start->modify('-89 days');
                break;
            default:
                $start->modify('-6 days');
        }

        return ['start' => $start, 'end' => $end];
    }

    private function getTotalProducts(): int
    {
        return wp_count_posts('product')->publish ?? 0;
    }

    private function getSyncedProductsCount(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_is_synced' 
            AND meta_value = '1'"
        );
    }

    private function getTotalOrders(): int
    {
        return wp_count_posts('shop_order')->publish ?? 0;
    }

    private function getPendingOrdersCount(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status = 'wc-pending'
            AND pm.meta_key = '_printify_order_id' IS NULL"
        );
    }

    private function getFailedOrdersCount(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND pm.meta_key = '_printify_sync_failed'
            AND pm.meta_value = '1'"
        );
    }

    private function getRevenueStats(): array
    {
        global $wpdb;

        $stats = [
            'today' => 0,
            'week' => 0,
            'month' => 0
        ];

        // Today's revenue
        $stats['today'] = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-processing', 'wc-completed')
            AND pm.meta_key = '_order_total'
            AND p.post_date >= %s",
            date('Y-m-d 00:00:00')
        ));

        // This week's revenue
        $stats['week'] = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-processing', 'wc-completed')
            AND pm.meta_key = '_order_total'
            AND p.post_date >= %s",
            date('Y-m-d 00:00:00', strtotime('-7 days'))
        ));

        // This month's revenue
        $stats['month'] = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-processing', 'wc-completed')
            AND pm.meta_key = '_order_total'
            AND p.post_date >= %s",
            date('Y-m-01 00:00:00')
        ));

        return $stats;
    }

    private function getApiKey(): string
    {
        return get_option(self::OPTION_PREFIX . 'api_key', '');
    }
}