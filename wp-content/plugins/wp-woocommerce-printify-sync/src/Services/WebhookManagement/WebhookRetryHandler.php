<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\WebhookManagement;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class WebhookRetryHandler
{
    use TimeStampTrait;

    private const MAX_RETRIES = 5;
    private const RETRY_DELAYS = [
        1 => 300,    // 5 minutes
        2 => 900,    // 15 minutes
        3 => 3600,   // 1 hour
        4 => 7200,   // 2 hours
        5 => 14400   // 4 hours
    ];

    private WebhookManager $webhookManager;
    private LoggerInterface $logger;
    private DatabaseInterface $db;

    public function __construct(
        WebhookManager $webhookManager,
        LoggerInterface $logger,
        DatabaseInterface $db
    ) {
        $this->webhookManager = $webhookManager;
        $this->logger = $logger;
        $this->db = $db;
    }

    public function handleFailedWebhook(
        string $webhookId,
        string $topic,
        array $payload,
        ?\Exception $error = null
    ): void {
        try {
            $retryCount = $this->getRetryCount($webhookId);

            if ($retryCount >= self::MAX_RETRIES) {
                $this->handleMaxRetriesExceeded($webhookId, $topic, $payload, $error);
                return;
            }

            $this->scheduleRetry($webhookId, $topic, $payload, $retryCount + 1);

            $this->logger->info('Webhook retry scheduled', [
                'webhook_id' => $webhookId,
                'topic' => $topic,
                'retry_count' => $retryCount + 1,
                'next_retry' => date(
                    'Y-m-d H:i:s',
                    time() + self::RETRY_DELAYS[$retryCount + 1]
                ),
                'timestamp' => $this->getCurrentTime()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle webhook retry', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
        }
    }

    private function getRetryCount(string $webhookId): int
    {
        return (int) $this->db->get_var($this->db->prepare(
            "SELECT retry_count FROM {$this->db->prefix}wpwps_webhook_logs
            WHERE webhook_id = %s
            ORDER BY created_at DESC
            LIMIT 1",
            $webhookId
        )) ?? 0;
    }

    private function scheduleRetry(
        string $webhookId,
        string $topic,
        array $payload,
        int $retryCount
    ): void {
        $delay = self::RETRY_DELAYS[$retryCount] ?? end(self::RETRY_DELAYS);

        wp_schedule_single_event(
            time() + $delay,
            'wpwps_retry_webhook',
            [
                'webhook_id' => $webhookId,
                'topic' => $topic,
                'payload' => $payload,
                'retry_count' => $retryCount
            ]
        );

        // Log retry attempt
        $this->db->insert(
            $this->db->prefix . 'wpwps_webhook_logs',
            [
                'webhook_id' => $webhookId,
                'topic' => $topic,
                'payload' => json_encode($payload),
                'retry_count' => $retryCount,
                'next_retry' => date('Y-m-d H:i:s', time() + $delay),
                'created_at' => $this->getCurrentTime()
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
    }

    private function handleMaxRetriesExceeded(
        string $webhookId,
        string $topic,
        array $payload,
        ?\Exception $error
    ): void {
        $this->logger->error('Maximum webhook retries exceeded', [
            'webhook_id' => $webhookId,
            'topic' => $topic,
            'last_error' => $error ? $error->getMessage() : null,
            'timestamp' => $this->getCurrentTime()
        ]);

        // Create support ticket
        $this->createSupportTicket($webhookId, $topic, $payload, $error);

        // Notify admin
        $this->notifyAdminOfFailure($webhookId, $topic, $payload, $error);
    }

    private function createSupportTicket(
        string $webhookId,
        string $topic,
        array $payload,
        ?\Exception $error
    ): void {
        $ticketData = [
            'title' => "Webhook Failed: {$topic}",
            'description' => $this->formatTicketDescription($webhookId, $topic, $payload, $error),
            'priority' => 'high',
            'category' => 'webhook_failure'
        ];

        // Integration with support ticket system would go here
    }

    private function notifyAdminOfFailure(
        string $webhookId,
        string $topic,
        array $payload,
        ?\Exception $error
    ): void {
        $adminEmail = get_option('admin_email');
        $blogName = get_bloginfo('name');

        $subject = sprintf(
            '[%s] Critical: Webhook Failure for %s',
            $blogName,
            $topic
        );

        $message = $this->formatFailureEmail($webhookId, $topic, $payload, $error);

        wp_mail($adminEmail, $subject, $message);
    }

    private function formatFailureEmail(
        string $webhookId,
        string $topic,
        array $payload,
        ?\Exception $error
    ): string {
        return sprintf(
            "Webhook has failed after maximum retries\n\n" .
            "Topic: %s\n" .
            "Webhook ID: %s\n" .
            "Last Error: %s\n\n" .
            "Payload:\n%s\n\n" .
            "Time: %s",
            $topic,
            $webhookId,
            $error ? $error->getMessage() : 'Unknown error',
            json_encode($payload, JSON_PRETTY_PRINT),
            $this->getCurrentTime()
        );
    }
}