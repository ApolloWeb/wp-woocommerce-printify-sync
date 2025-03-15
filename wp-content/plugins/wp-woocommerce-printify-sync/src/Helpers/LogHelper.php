<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class LogHelper
{
    private string $currentTime;
    private string $currentUser;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:43:33';
        $this->currentUser = 'ApolloWeb';
    }

    public function log(string $message, string $type = 'info', array $context = []): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_sync_log',
            [
                'message' => $message,
                'type' => $type,
                'context' => json_encode($context),
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ]
        );
    }

    public function getRecentLogs(int $limit = 10): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpwps_sync_log ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
    }
}