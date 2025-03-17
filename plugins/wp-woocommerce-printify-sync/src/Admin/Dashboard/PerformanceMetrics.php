<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard;

class PerformanceMetrics
{
    public function getMetrics(): array
    {
        return [
            'sync_status' => $this->getSyncStatus(),
            'api_health' => $this->getApiHealth(),
            'recent_performance' => $this->getRecentPerformance(),
            'error_rate' => $this->getErrorRate(),
        ];
    }

    private function getSyncStatus(): array
    {
        $last_sync = get_option('wpwps_last_sync_complete', '');
        $sync_status = get_option('wpwps_sync_status', 'unknown');
        
        return [
            'last_sync' => $last_sync ? human_time_diff(strtotime($last_sync)) : 'Never',
            'status' => $sync_status,
            'products_synced' => get_option('wpwps_sync_products_total', 0),
            'success_rate' => $this->calculateSyncSuccessRate(),
        ];
    }

    private function getApiHealth(): array
    {
        $api_calls = $this->getRecentApiCalls();
        
        return [
            'uptime' => $this->calculateApiUptime(),
            'response_time' => $this->calculateAverageResponseTime($api_calls),
            'error_count' => $this->countApiErrors($api_calls),
            'rate_limits' => $this->getRateLimitStatus(),
        ];
    }

    private function getRecentPerformance(): array
    {
        global $wpdb;
        
        $stats = $wpdb->get_results("
            SELECT DATE(created_at) as date,
                   COUNT(*) as total_orders,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                   AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_fulfillment_time
            FROM {$wpdb->prefix}wpwps_fulfillment_tracking
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");

        return array_map(function($stat) {
            return [
                'date' => $stat->date,
                'total_orders' => (int)$stat->total_orders,
                'completed_orders' => (int)$stat->completed_orders,
                'fulfillment_time' => round($stat->avg_fulfillment_time, 2),
            ];
        }, $stats);
    }

    private function getErrorRate(): array
    {
        global $wpdb;
        
        $errors = $wpdb->get_results("
            SELECT error_type, COUNT(*) as count
            FROM {$wpdb->prefix}wpwps_error_log
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY error_type
            ORDER BY count DESC
            LIMIT 5
        ");

        return array_map(function($error) {
            return [
                'type' => $error->error_type,
                'count' => (int)$error->count,
            ];
        }, $errors);
    }
}