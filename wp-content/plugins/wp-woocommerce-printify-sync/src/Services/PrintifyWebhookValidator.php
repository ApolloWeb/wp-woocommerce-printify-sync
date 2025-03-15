<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\WebhookValidatorInterface;

class PrintifyWebhookValidator implements WebhookValidatorInterface
{
    private string $currentTime = '2025-03-15 19:52:43';
    private string $currentUser = 'ApolloWeb';
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = get_option('wpwps_webhook_secret', '');
    }

    public function validate(string $payload, string $signature): bool
    {
        if (empty($this->secretKey) || empty($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->secretKey);
        return hash_equals($expectedSignature, $signature);
    }

    public function getSecret(): string
    {
        return $this->secretKey;
    }
}