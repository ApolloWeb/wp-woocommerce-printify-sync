<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Logger;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Context\SyncContext;

class Logger implements LoggerInterface
{
    private const TABLE_NAME = 'wpwps_logs';
    private SyncContext $context;
    private string $logLevel;

    public function __construct(SyncContext $context)
    {
        $this->context = $context;
        $this->logLevel = get_option('wpwps_log_level', 'info');
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        global $wpdb;

        $data = [
            'level' => $level,
            'message' => $message,
            'context' => json_encode($context),
            'created_at' => $this->context->getCurrentTime(),
            'created_by' => $this->context->getCurrentUser()
        ];

        $wpdb->insert($wpdb->prefix . self::TABLE_NAME, $data);

        if ($level === 'error') {
            error_log(sprintf(
                '[WPWPS] %s: %s - Context: %s',
                strtoupper($level),
                $message,
                json_encode($context)
            ));
        }
    }

    public function getRecentLogs(int $limit = 100, array $filters = []): array
    {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->prefix}" . self::TABLE_NAME;
        $where = [];
        $params = [];

        if (!empty($filters['level'])) {
            $where[] = 'level = %s';
            $params[] = $filters['level'];
        }

        if (!empty($filters['sync_id'])) {
            $where[] = "context LIKE %s";
            $params[] = '%"sync_id":"' . $filters['sync_id'] . '"%';
        }

        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        $query .= ' ORDER BY created_at DESC LIMIT %d';
        $params[] = $limit;

        return $wpdb->get_results(
            $wpdb->prepare($query, $params)
        );
    }

    private function shouldLog(string $level): bool
    {
        $levels = [
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3
        ];

        return $levels[$level] >= $levels[$this->logLevel];
    }

    public static function createTable(): void
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}" . self::TABLE_NAME . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext NOT NULL,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            PRIMARY KEY (id),
            KEY level (level),
            KEY created_at (created_at)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}