<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\RealTime;

class DataStreamManager
{
    private const STREAM_CHANNELS = [
        'sync_status',
        'api_metrics',
        'order_updates',
        'error_alerts'
    ];

    private $redis;
    private $options;

    public function __construct()
    {
        $this->options = get_option('wpwps_realtime_settings', []);
        $this->initializeRedis();
    }

    private function initializeRedis(): void
    {
        try {
            $this->redis = new \Redis();
            $this->redis->connect(
                $this->options['redis_host'] ?? '127.0.0.1',
                $this->options['redis_port'] ?? 6379
            );
        } catch (\Exception $e) {
            error_log('Redis connection failed: ' . $e->getMessage());
        }
    }

    public function publishUpdate(string $channel, array $data): bool
    {
        if (!in_array($channel, self::STREAM_CHANNELS)) {
            return false;
        }

        $message = json_encode([
            'timestamp' => current_time('mysql'),
            'channel' => $channel,
            'data' => $data
        ]);

        return $this->redis->publish("wpwps_$channel", $message);
    }

    public function getLatestMetrics(): array
    {
        global $wpdb;

        return [
            'sync_status' => $this->getSyncMetrics($wpdb),
            'api_health' => $this->getApiMetrics($wpdb),
            'order_stats' => $this->getOrderMetrics($wpdb),
            'error_rates' => $this->getErrorMetrics($wpdb)
        ];
    }

    private function getSyncMetrics($wpdb): array
    {
        return $wpdb->get_row("
            SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN sync_status = 'success' THEN 1 ELSE 0 END) as synced,
                SUM(CASE WHEN sync_status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN sync_status = 'error' THEN 1 ELSE 0 END) as failed
            FROM {$wpdb->prefix}wpwps_product_sync_status
            WHERE last_sync >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ", ARRAY_A);
    }

    private function getApiMetrics($wpdb): array
    {
        return $wpdb->get_row("
            SELECT 
                AVG(response_time) as avg_response_time,
                COUNT(*) as total_requests,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors
            FROM {$wpdb->prefix}wpwps_api_log
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ", ARRAY_A);
    }

    private function getOrderMetrics($wpdb): array
    {
        return $wpdb->get_row("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM {$wpdb->prefix}wpwps_fulfillment_tracking
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ", ARRAY_A);
    }

    private function getErrorMetrics($wpdb): array
    {
        return $wpdb->get_results("
            SELECT 
                error_type,
                COUNT(*) as count
            FROM {$wpdb->prefix}wpwps_error_log
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY error_type
            ORDER BY count DESC
            LIMIT 5
        ", ARRAY_A);
    }
}