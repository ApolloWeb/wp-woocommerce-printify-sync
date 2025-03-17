<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\WebhookManagement;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class WebhookMonitor
{
    use TimeStampTrait;

    private const HEALTH_CHECK_INTERVAL = 300; // 5 minutes
    private const MAX_FAIL_COUNT = 3;
    private const ALERT_THRESHOLD = 0.8; // 80% failure rate triggers alert

    private WebhookManager $webhookManager;
    private LoggerInterface $logger;
    private ConfigService $config;
    private DatabaseInterface $db;

    public function __construct(
        WebhookManager $webhookManager,
        LoggerInterface $logger,
        ConfigService $config,
        DatabaseInterface $db
    ) {
        $this->webhookManager = $webhookManager;
        $this->logger = $logger;
        $this->config = $config;
        $this->db = $db;
    }

    public function monitorHealth(): void
    {
        try {
            $stats = $this->getWebhookStats();
            $this->analyzeWebhookHealth($stats);
            $this->cleanupOldLogs();
            
            // Log monitoring results
            $this->logger->info('Webhook health check completed', [
                'stats' => $stats,
                'timestamp' => $this->getCurrentTime()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Webhook monitoring failed', [
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
        }
    }

    private function getWebhookStats(): array
    {
        $query = "
            SELECT 
                topic,
                COUNT(*) as total,
                SUM(CASE WHEN processed = 1 THEN 1 ELSE 0 END) as processed,
                SUM(CASE WHEN processed = 0 THEN 1 ELSE 0 END) as failed,
                MAX(created_at) as last_received
            FROM {$this->db->prefix}wpwps_webhook_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY topic
        ";

        return $this->db->get_results($query, ARRAY_A);
    }

    private function analyzeWebhookHealth(array $stats): void
    {
        foreach ($stats as $stat) {
            $failureRate = $stat['total'] > 0 ? 
                $stat['failed'] / $stat['total'] : 0;

            if ($failureRate >= self::ALERT_THRESHOLD) {
                $this->handleHighFailureRate($stat, $failureRate);
            }

            // Check for missing webhooks
            $timeSinceLastWebhook = strtotime($this->getCurrentTime()) - 
                strtotime($stat['last_received']);
            
            if ($timeSinceLastWebhook > self::HEALTH_CHECK_INTERVAL * 2) {
                $this->handleMissingWebhooks($stat, $timeSinceLastWebhook);
            }
        }
    }

    private function handleHighFailureRate(array $stat, float $failureRate): void
    {
        $this->logger->warning('High webhook failure rate detected', [
            'topic' => $stat['topic'],
            'failure_rate' => $failureRate,
            'total_requests' => $stat['total'],
            'failed_requests' => $stat['failed'],
            'timestamp' => $this->getCurrentTime()
        ]);

        // Notify admin
        $this->notifyAdmin('webhook_failure_alert', [
            'topic' => $stat['topic'],
            'failure_rate' => round($failureRate * 100, 2),
            'total_requests' => $stat['total'],
            'failed_requests' => $stat['failed']
        ]);

        // Trigger automatic recovery
        $this->triggerRecoveryProcedure($stat['topic']);
    }

    private function handleMissingWebhooks(array $stat, int $timeSince): void
    {
        $this->logger->warning('Missing webhooks detected', [
            'topic' => $stat['topic'],
            'minutes_since_last' => round($timeSince / 60),
            'timestamp' => $this->getCurrentTime()
        ]);

        // Attempt to recreate webhook
        $this->webhookManager->recreateWebhook($stat['topic']);
    }

    private function notifyAdmin(string $type, array $data): void
    {
        $adminEmail = get_option('admin_email');
        $blogName = get_bloginfo('name');

        $subject = sprintf(
            '[%s] Webhook Alert: %s',
            $blogName,
            ucfirst(str_replace('_', ' ', $type))
        );

        $message = $this->formatAlertMessage($type, $data);

        wp_mail($adminEmail, $subject, $message);
    }

    private function cleanupOldLogs(): void
    {
        $days = $this->config->get('webhook_cleanup_days', 30);
        
        $this->db->query($this->db->prepare(
            "DELETE FROM {$this->db->prefix}wpwps_webhook_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
}