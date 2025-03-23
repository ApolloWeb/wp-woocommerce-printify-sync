<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Api;

class WebhookHandler {
    private $logger;
    private $settings;
    private $events = [
        'order.created',
        'order.updated',
        'order.canceled',
        'order.failed',
        'shipping.update',
        'product.published',
        'product.unpublished',
        'product.updated',
        'product.deleted',
        'variant.created',
        'variant.updated',
        'variant.deleted',
        'shop.disconnected'
    ];

    public function init(): void {
        add_action('woocommerce_api_wpwps_webhook', [$this, 'handleWebhook']);
    }

    public function handleWebhook(): void {
        // Verify webhook signature
        if (!$this->verifyWebhookSignature()) {
            status_header(401);
            exit('Invalid signature');
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        $event = $_SERVER['HTTP_X_PRINTIFY_EVENT'] ?? '';

        if (!in_array($event, $this->events)) {
            status_header(400);
            exit('Invalid event type');
        }

        $this->logger->log("Received webhook: {$event}", 'info', $payload);

        try {
            switch ($event) {
                case 'order.created':
                case 'order.updated':
                    $this->handleOrderWebhook($payload);
                    break;

                case 'shipping.update':
                    $this->handleShippingUpdate($payload);
                    break;

                case 'product.published':
                case 'product.updated':
                    $this->handleProductWebhook($payload);
                    break;

                case 'variant.created':
                case 'variant.updated':
                    $this->handleVariantWebhook($payload);
                    break;

                case 'shop.disconnected':
                    $this->handleShopDisconnected($payload);
                    break;
            }

            status_header(200);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            $this->logger->log("Webhook error: " . $e->getMessage(), 'error', $payload);
            status_header(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    private function verifyWebhookSignature(): bool {
        $signature = $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] ?? '';
        $payload = file_get_contents('php://input');
        $secret = $this->settings->get('webhook_secret');
        
        return hash_equals(
            hash_hmac('sha256', $payload, $secret),
            $signature
        );
    }
}
