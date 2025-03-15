<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class RequestTracker
{
    private string $currentTime;
    private string $currentUser;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:21:23';
        $this->currentUser = 'ApolloWeb';
    }

    public function trackRequest(string $endpoint, array $params, array $response): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_api_requests',
            [
                'endpoint' => $endpoint,
                'params' => json_encode($params),
                'response' => json_encode($response),
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
    }

    public function getRequestStats(): array
    {
        global $wpdb;

        $stats = $wpdb->get_results("
            SELECT 
                endpoint,
                COUNT(*) as total_requests,
                AVG(TIMESTAMPDIFF(MICROSECOND, created_at, completed_at)) as avg_response_time,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count
            FROM {$wpdb->prefix}wpwps_api_requests
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY endpoint
        ");

        return array_map(fn($stat) => (array)$stat, $stats);
    }
}