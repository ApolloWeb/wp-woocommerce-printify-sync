<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard\AbstractWidget;

class APIHealthWidget extends AbstractWidget
{
    protected $id = 'api_health';
    protected $title = 'API & Webhook Health';

    protected function getData(): array
    {
        return [
            'api_status' => $this->checkAPIStatus(),
            'webhook_status' => $this->checkWebhookStatus(),
            'rate_limit' => [
                'remaining' => get_option('wpwps_api_rate_limit_remaining', 0),
                'reset' => get_option('wpwps_api_rate_limit_reset', 0),
            ],
            'last_errors' => $this->getLastErrors()
        ];
    }

    private function checkAPIStatus(): bool
    {
        $last_success = get_option('wpwps_api_last_success', 0);
        return (time() - $last_success) < 300; // Consider healthy if success within 5 minutes
    }

    private function checkWebhookStatus(): bool
    {
        $last_webhook = get_option('wpwps_webhook_last_received', 0);
        return (time() - $last_webhook) < 3600; // Consider healthy if received within 1 hour
    }

    private function getLastErrors(): array
    {
        return get_option('wpwps_api_last_errors', []);
    }
}
