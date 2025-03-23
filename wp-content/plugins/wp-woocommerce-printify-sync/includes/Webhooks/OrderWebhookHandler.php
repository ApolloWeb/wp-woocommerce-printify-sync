<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

class OrderWebhookHandler {
    private $logger;
    private $status_map = [
        'pending' => 'processing',
        'in_production' => 'processing',
        'shipped' => 'completed',
        'canceled' => 'cancelled'
    ];

    public function __construct(Logger $logger) {
        $this->logger = $logger;
        add_action('woocommerce_api_printify_webhook', [$this, 'handleWebhook']);
    }

    public function handleWebhook(): void {
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        if (!$this->validateWebhook($data)) {
            status_header(400);
            die('Invalid webhook data');
        }

        $this->processWebhook($data);
    }

    private function processWebhook(array $data): void {
        $order = wc_get_order($data['external_id']);
        if (!$order) return;

        $status = $this->mapStatus($data['status']);
        $order->update_status($status);
        
        if (isset($data['tracking'])) {
            $this->updateTracking($order, $data['tracking']);
        }

        $order->save();
    }

    private function validateWebhook($data): bool {
        return isset($data['external_id'], $data['status']);
    }
}
