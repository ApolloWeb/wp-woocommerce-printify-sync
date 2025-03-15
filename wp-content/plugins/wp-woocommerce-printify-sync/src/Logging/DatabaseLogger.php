<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Logging;

class DatabaseLogger extends AbstractLogger
{
    private const TABLE_NAME = 'wpwps_logs';

    public function __construct()
    {
        $this->createLogsTableIfNotExists();
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . self::TABLE_NAME,
            [
                'level' => $level,
                'message' => $this->interpolate($message, $context),
                'context' => json_encode($context),
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser,
                'component' => $context['component'] ?? 'general'
            ]
        );
    }

    private function createLogsTableIfNotExists(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}" . self::TABLE_NAME . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            component varchar(50) NOT NULL,
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            PRIMARY KEY  (id),
            KEY level (level),
            KEY component (component),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}