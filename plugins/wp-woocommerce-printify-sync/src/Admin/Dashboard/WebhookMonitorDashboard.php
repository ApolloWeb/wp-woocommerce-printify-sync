<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard;

use ApolloWeb\WPWooCommercePrintifySync\Services\WebhookManagement\WebhookMonitor;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class WebhookMonitorDashboard
{
    use TimeStampTrait;

    private WebhookMonitor $webhookMonitor;
    private LoggerInterface $logger;

    public function __construct(
        WebhookMonitor $webhookMonitor,
        LoggerInterface $logger
    ) {
        $this->webhookMonitor = $webhookMonitor;
        $this->logger = $logger;
    }

    public function render(): void
    {
        $stats = $this->getWebhookStats();
        $health = $this->webhookMonitor->getHealth();
        $recentEvents = $this->getRecentEvents();

        include WPWPS_PLUGIN_DIR . '/templates/admin/webhook-monitor.php';
    }

    private function getWebhookStats(): array
    {
        return [
            'total_webhooks' => $this->webhookMonitor->getTotalWebhooks(),
            'active_webhooks' => $this->webhookMonitor->getActiveWebhooks(),
            'failed_webhooks' => $this->webhookMonitor->getFailedWebhooks(),
            'success_rate' => $this->webhookMonitor->getSuccessRate(),
            'average_response_time' => $this->webhookMonitor->getAverageResponseTime(),
            'webhook_volume' => $this->webhookMonitor->getWebhookVolume('24h'),
        ];
    }

    private function getRecentEvents(): array
    {
        return $this->webhookMonitor->getRecentEvents(20);
    }

    public function getAjaxStats(): void
    {
        check_ajax_referer('wpwps_admin');

        wp_send_json_success([
            'stats' => $this->getWebhookStats(),
            'health' => $this->webhookMonitor->getHealth(),
            'events' => $this->getRecentEvents(),
            'timestamp' => $this->getCurrentTime()
        ]);
    }
}