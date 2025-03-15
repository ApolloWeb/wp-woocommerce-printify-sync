<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\DashboardDataProviderInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\SystemTimeInterface;

class DashboardDataProvider implements DashboardDataProviderInterface
{
    private SystemTimeInterface $systemTime;
    private UserContext $userContext;

    public function __construct(
        SystemTimeInterface $systemTime,
        UserContext $userContext
    ) {
        $this->systemTime = $systemTime;
        $this->userContext = $userContext;
    }

    public function getTotalProducts(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE post_type = %s AND post_status != 'trash'",
                'product'
            )
        );
    }

    public function getSyncedProducts(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_key = %s 
                AND p.post_type = %s 
                AND p.post_status != 'trash'",
                '_printify_sync_status',
                'product'
            )
        );
    }

    public function getPendingSync(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_key = %s 
                AND pm.meta_value = %s
                AND p.post_type = %s 
                AND p.post_status != 'trash'",
                '_printify_sync_status',
                'pending',
                'product'
            )
        );
    }

    public function getSyncErrors(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_sync_log
                WHERE status = %s 
                AND created_at >= %s",
                'error',
                $this->systemTime->formatDateTime(
                    $this->systemTime->getCurrentUTCDateTime()->modify('-24 hours')
                )
            )
        );
    }

    public function getRecentActivity(): array
    {
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpwps_sync_log
                ORDER BY created_at DESC
                LIMIT %d",
                10
            ),
            ARRAY_A
        );

        return array_map(function($row) {
            return [
                'date' => $this->systemTime->formatDateTime(
                    new \DateTime($row['created_at'])
                ),
                'type' => $row['sync_type'],
                'status' => $row['status'],
                'status_class' => $this->getStatusClass($row['status']),
                'details' => $row['message']
            ];
        }, $results ?? []);
    }

    public function getSyncHistory(): array
    {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT 
                DATE(created_at) as sync_date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
            FROM {$wpdb->prefix}wpwps_sync_log
            GROUP BY DATE(created_at)
            ORDER BY sync_date DESC
            LIMIT 7",
            ARRAY_A
        );

        return array_reverse($results ?? []);
    }

    public function getProductCategories(): array
    {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true
        ]);

        return array_map(function($category) {
            return [
                'name' => $category->name,
                'count' => $category->count
            ];
        }, $categories ?? []);
    }

    public function getLastSyncTime(): string
    {
        global $wpdb;
        $lastSync = $wpdb->get_var(
            "SELECT created_at
            FROM {$wpdb->prefix}wpwps_sync_log
            ORDER BY created_at DESC
            LIMIT 1"
        );

        return $lastSync ? 
            $this->systemTime->formatDateTime(new \DateTime($lastSync)) :
            $this->systemTime->formatDateTime($this->systemTime->getCurrentUTCDateTime());
    }

    private function getStatusClass(string $status): string
    {
        return match ($status) {
            'success' => 'success',
            'error' => 'danger',
            'pending' => 'warning',
            default => 'info'
        };
    }
}