<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Models\Log;

class LogManager extends AbstractService
{
    private CloudStorageService $cloudStorage;
    private const LOG_RETENTION_DAYS = 14;

    public function __construct(
        CloudStorageService $cloudStorage,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        parent::__construct($logger, $config);
        $this->cloudStorage = $cloudStorage;
    }

    public function getLogs(array $filters = []): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'printify_logs';
        
        $query = "SELECT * FROM {$table} WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (message LIKE %s OR context LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($filters['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($filters['search']) . '%';
        }

        if (!empty($filters['level'])) {
            $query .= " AND level = %s";
            $params[] = $filters['level'];
        }

        if (!empty($filters['date_from'])) {
            $query .= " AND created_at >= %s";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $query .= " AND created_at <= %s";
            $params[] = $filters['date_to'];
        }

        $query .= " ORDER BY created_at DESC";

        if (isset($filters['per_page'])) {
            $query .= " LIMIT %d OFFSET %d";
            $params[] = $filters['per_page'];
            $params[] = ($filters['page'] - 1) * $filters['per_page'];
        }

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    public function archiveLogs(): void
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-" . self::LOG_RETENTION_DAYS . " days"));
        
        // Get logs to archive
        $logs = $this->getLogs(['date_to' => $cutoffDate]);
        
        if (empty($logs)) {
            return;
        }

        // Group logs by date
        $groupedLogs = [];
        foreach ($logs as $log) {
            $date = date('Y-m-d', strtotime($log->created_at));
            $groupedLogs[$date][] = $log;
        }

        // Archive each group
        foreach ($groupedLogs as $date => $logs) {
            $year = date('Y', strtotime($date));
            $month = date('m', strtotime($date));
            $day = date('d', strtotime($date));
            
            $path = "logs/{$year}/{$month}/{$day}/logs.json";
            $uploaded = $this->cloudStorage->upload($path, json_encode($logs));
            
            if ($uploaded) {
                $this->updateLogsWithCloudLink($logs, $uploaded['url']);
            }
        }
    }

    private function updateLogsWithCloudLink(array $logs, string $url): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'printify_logs';
        $ids = array_map(fn($log) => $log->id, $logs);
        
        $wpdb->update(
            $table,
            ['cloud_url' => $url, 'archived' => 1],
            ['id' => $ids],
            ['%s', '%d'],
            ['%d']
        );
    }
}