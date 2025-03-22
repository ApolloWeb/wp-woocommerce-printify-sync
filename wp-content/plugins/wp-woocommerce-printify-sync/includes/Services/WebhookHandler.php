<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class WebhookHandler {
    private $logger;
    private $secret;

    public function __construct(Logger $logger, Settings $settings) {
        $this->logger = $logger;
        $this->secret = $settings->get('webhook_secret');
    }

    public function init(): void {
        add_action('woocommerce_api_printify_webhook', [$this, 'handleWebhook']);
    }

    public function handleWebhook(): void {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] ?? '';

        if (!$this->verifySignature($payload, $signature)) {
            status_header(401);
            die('Invalid signature');
        }

        $data = json_decode($payload, true);
        $this->processWebhook($data);
    }

    private function verifySignature(string $payload, string $signature): bool {
        return hash_equals(
            hash_hmac('sha256', $payload, $this->secret),
            $signature
        );
    }

    private function processWebhook(array $data): void {
        // Webhook processing implementation
    }
}
