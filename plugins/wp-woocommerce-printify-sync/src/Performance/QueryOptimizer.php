<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Performance;

class QueryOptimizer
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function getFulfillmentsByStatus(string $status, int $limit = 10, int $offset = 0): array
    {
        $cache_key = "fulfillment_status_{$status}_{$limit}_{$offset}";
        $cached = wp_cache_get($cache_key, 'wpwps_queries');

        if ($cached !== false) {
            return $cached;
        }

        $results = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT ft.*, 
                   p.post_status as order_status,
                   pm.meta_value as printify_data
            FROM {$this->wpdb->prefix}wpwps_fulfillment_tracking ft
            LEFT JOIN {$this->wpdb->posts} p ON ft.order_id = p.ID
            LEFT JOIN {$this->wpdb->postmeta} pm ON ft.order_id = pm.post_id
                AND pm.meta_key = '_printify_order_data'
            WHERE ft.status = %s
            ORDER BY ft.created_at DESC
            LIMIT %d OFFSET %d
        ", $status, $limit, $offset), ARRAY_A);

        wp_cache_set($cache_key, $results, 'wpwps_queries', 300); // Cache for 5 minutes

        return $results;
    }

    public function getRecentSyncErrors(int $days = 7): array
    {
        $cache_key = "sync_errors_{$days}";
        $cached = wp_cache_get($cache_key, 'wpwps_queries');

        if ($cached !== false) {
            return $cached;
        }

        $results = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT product_id, 
                   printify_id,
                   sync_status,
                   sync_message,
                   last_sync
            FROM {$this->wpdb->prefix}wpwps_product_sync_status
            WHERE sync_status = 'error'
            AND last_sync >= DATE_SUB(NOW(), INTERVAL %d DAY)
            ORDER BY last_sync DESC
        ", $days), ARRAY_A);

        wp_cache_set($cache_key, $results, 'wpwps_queries', 300);

        return $results;
    }

    public function cleanupOldRecords(): int
    {
        // Clean up old fulfillment records
        $deleted = $this->wpdb->query("
            DELETE FROM {$this->wpdb->prefix}wpwps_fulfillment_tracking
            WHERE status = 'completed'
            AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");

        // Clean up old sync logs
        $this->wpdb->query("
            DELETE FROM {$this->wpdb->prefix}wpwps_sync_log
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        return $deleted;
    }
}