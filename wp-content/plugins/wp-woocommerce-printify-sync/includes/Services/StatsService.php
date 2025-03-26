<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class StatsService {
    private $logger_service;
    private $job_service;

    public function __construct() {
        $this->logger_service = new LoggerService();
        $this->job_service = new JobService();
    }

    public function getDashboardStats(): array {
        return [
            'orders' => $this->getOrderStats(),
            'products' => $this->getProductStats(),
            'support' => $this->getSupportStats(),
            'system' => $this->getSystemStats()
        ];
    }

    private function getOrderStats(): array {
        global $wpdb;

        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN post_status = 'wc-pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN post_status = 'wc-processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN post_status = 'wc-completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN post_status = 'wc-cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        // Get total revenue for last 30 days
        $revenue = $wpdb->get_var("
            SELECT SUM(meta_value)
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE meta_key = '_order_total'
            AND p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing')
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        return [
            'total' => (int) $stats->total,
            'pending' => (int) $stats->pending,
            'processing' => (int) $stats->processing,
            'completed' => (int) $stats->completed,
            'cancelled' => (int) $stats->cancelled,
            'revenue' => round(floatval($revenue), 2),
            'chart_data' => $this->getOrderChartData()
        ];
    }

    private function getProductStats(): array {
        global $wpdb;

        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN post_status = 'publish' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN post_status = 'draft' THEN 1 ELSE 0 END) as draft
            FROM {$wpdb->posts}
            WHERE post_type = 'product'
            AND ID IN (
                SELECT post_id 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_printify_product_id'
            )
        ");

        // Get top selling products
        $top_sellers = $wpdb->get_results("
            SELECT 
                p.ID,
                p.post_title,
                COUNT(*) as sales_count,
                SUM(order_item_meta.meta_value) as total_sales
            FROM {$wpdb->posts} p
            JOIN {$wpdb->prefix}woocommerce_order_items order_items 
                ON order_items.order_item_type = 'line_item'
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta order_item_meta 
                ON order_items.order_item_id = order_item_meta.order_item_id
            WHERE p.post_type = 'product'
            AND order_item_meta.meta_key = '_line_total'
            AND p.ID IN (
                SELECT post_id 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_printify_product_id'
            )
            GROUP BY p.ID
            ORDER BY sales_count DESC
            LIMIT 5
        ");

        return [
            'total' => (int) $stats->total,
            'active' => (int) $stats->active,
            'draft' => (int) $stats->draft,
            'top_sellers' => array_map(function($product) {
                return [
                    'id' => $product->ID,
                    'title' => $product->post_title,
                    'sales_count' => (int) $product->sales_count,
                    'total_sales' => round(floatval($product->total_sales), 2)
                ];
            }, $top_sellers)
        ];
    }

    private function getSupportStats(): array {
        global $wpdb;

        $tickets = get_posts([
            'post_type' => 'wpwps_ticket',
            'post_status' => 'any',
            'date_query' => [
                'after' => '30 days ago'
            ],
            'tax_query' => [
                [
                    'taxonomy' => 'ticket_status',
                    'field' => 'slug',
                    'terms' => ['new', 'awaiting_evidence', 'in_progress', 'resolved']
                ]
            ]
        ]);

        $stats = [
            'total' => count($tickets),
            'new' => 0,
            'awaiting_evidence' => 0,
            'in_progress' => 0,
            'resolved' => 0,
            'avg_response_time' => 0,
            'avg_resolution_time' => 0
        ];

        $total_response_time = 0;
        $total_resolution_time = 0;
        $response_count = 0;
        $resolution_count = 0;

        foreach ($tickets as $ticket) {
            $status = wp_get_object_terms($ticket->ID, 'ticket_status')[0]->slug ?? 'new';
            $stats[$status]++;

            $created = strtotime($ticket->post_date);
            $first_response = get_post_meta($ticket->ID, '_first_response_time', true);
            $resolved_time = get_post_meta($ticket->ID, '_resolved_time', true);

            if ($first_response) {
                $total_response_time += ($first_response - $created);
                $response_count++;
            }

            if ($resolved_time) {
                $total_resolution_time += ($resolved_time - $created);
                $resolution_count++;
            }
        }

        if ($response_count > 0) {
            $stats['avg_response_time'] = round($total_response_time / $response_count / 3600, 1); // in hours
        }

        if ($resolution_count > 0) {
            $stats['avg_resolution_time'] = round($total_resolution_time / $resolution_count / 3600, 1); // in hours
        }

        return $stats;
    }

    private function getSystemStats(): array {
        return [
            'logs' => $this->logger_service->getStats(),
            'jobs' => $this->job_service->getQueueStats(),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'wc_version' => WC()->version,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        ];
    }

    private function getOrderChartData(): array {
        global $wpdb;

        $results = $wpdb->get_results("
            SELECT 
                DATE(post_date) as date,
                COUNT(*) as orders,
                SUM(meta_value) as revenue
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-completed', 'wc-processing')
            AND meta_key = '_order_total'
            AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(post_date)
            ORDER BY date ASC
        ");

        $data = [];
        $current = strtotime('-30 days');
        $end = time();

        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $found = false;

            foreach ($results as $result) {
                if ($result->date === $date) {
                    $data[] = [
                        'date' => $date,
                        'orders' => (int) $result->orders,
                        'revenue' => round(floatval($result->revenue), 2)
                    ];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $data[] = [
                    'date' => $date,
                    'orders' => 0,
                    'revenue' => 0
                ];
            }

            $current = strtotime('+1 day', $current);
        }

        return $data;
    }

    public function getCustomReportData(array $params): array {
        $start_date = $params['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $params['end_date'] ?? date('Y-m-d');
        $metrics = $params['metrics'] ?? ['orders', 'revenue'];
        $group_by = $params['group_by'] ?? 'day';

        global $wpdb;

        $date_format = $group_by === 'month' ? '%Y-%m' : '%Y-%m-%d';
        $group_clause = "DATE_FORMAT(post_date, '$date_format')";

        $query = $wpdb->prepare("
            SELECT 
                $group_clause as period,
                COUNT(*) as orders,
                SUM(meta_value) as revenue
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-completed', 'wc-processing')
            AND meta_key = '_order_total'
            AND post_date BETWEEN %s AND %s
            GROUP BY period
            ORDER BY period ASC",
            $start_date,
            $end_date
        );

        $results = $wpdb->get_results($query);

        $data = [];
        foreach ($results as $row) {
            $item = ['period' => $row->period];
            foreach ($metrics as $metric) {
                $item[$metric] = $metric === 'revenue' ? 
                    round(floatval($row->$metric), 2) : 
                    (int) $row->$metric;
            }
            $data[] = $item;
        }

        return [
            'data' => $data,
            'totals' => [
                'orders' => array_sum(array_column($data, 'orders')),
                'revenue' => round(array_sum(array_column($data, 'revenue')), 2)
            ]
        ];
    }
}