<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

use ApolloWeb\WPWooCommercePrintifySync\Products\ProductSync;
use phpseclib3\Crypt\Hash;

class WebhookHandler
{
    private string $secret;
    private ProductSync $productSync;

    public function __construct()
    {
        $this->secret = get_option('wpwps_webhook_secret', '');
        $this->productSync = new ProductSync();
    }

    public function handle(): void
    {
        if (!$this->verifySignature()) {
            http_response_code(401);
            exit('Invalid signature');
        }

        $payload = $this->getPayload();
        if (!$payload) {
            http_response_code(400);
            exit('Invalid payload');
        }

        $this->processWebhook($payload);
        http_response_code(200);
        exit('OK');
    }

    private function verifySignature(): bool
    {
        if (empty($this->secret)) {
            return false;
        }

        $signature = $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] ?? '';
        $payload = file_get_contents('php://input');

        $hash = hash_hmac('sha256', $payload, $this->secret);
        return hash_equals($hash, $signature);
    }

    private function getPayload(): ?array
    {
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    private function processWebhook(array $payload): void
    {
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];

        switch ($event) {
            case 'product.created':
            case 'product.updated':
                do_action('wpwps_product_webhook', $data);
                break;

            case 'order.created':
            case 'order.updated':
                do_action('wpwps_order_webhook', $data);
                break;

            case 'shipping.updated':
                do_action('wpwps_shipping_webhook', $data);
                break;

            default:
                error_log("Unknown webhook event: {$event}");
                break;
        }
    }
}