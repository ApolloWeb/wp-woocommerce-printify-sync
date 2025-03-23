<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

class PrintifyWebhookHandler {
    private $logger;
    private $secret;
    private $events = [
        'order.created' => 'handleOrderCreated',
        'order.updated' => 'handleOrderUpdated',
        'shipping.update' => 'handleShippingUpdate',
        'product.published' => 'handleProductPublished'
    ];

    public function __construct(Logger $logger, Settings $settings) {
        $this->logger = $logger;
        $this->secret = $settings->get('webhook_secret');
    }

    public function handle(): void {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] ?? '';

        if (!$this->verifySignature($payload, $signature)) {
            $this->logger->log('Invalid webhook signature', 'error');
            status_header(401);
            die('Invalid signature');
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? '';

        if (isset($this->events[$event])) {
            $method = $this->events[$event];
            $this->$method($data);
        }
    }

    private function verifySignature(string $payload, string $signature): bool {
        return hash_equals(
            hash_hmac('sha256', $payload, $this->secret),
            $signature
        );
    }
}
