<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\WebhookManagement;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class WebhookHandler
{
    use TimeStampTrait;

    private const PRINTIFY_SIGNATURE_HEADER = 'X-Printify-Signature';
    private const WEBHOOK_EVENTS = [
        'order.created',
        'order.updated',
        'order.cancelled',
        'order.fulfilled',
        'product.created',
        'product.updated',
        'product.deleted',
        'shipping.updated',
        'stock.updated'
    ];

    private $config;
    private $logger;
    private $orderSync;
    private $productSync;
    private $stockSync;

    public function __construct(
        ConfigService $config,
        LoggerInterface $logger,
        OrderSyncService $orderSync,
        ProductSyncService $productSync,
        StockSyncService $stockSync
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->orderSync = $orderSync;
        $this->productSync = $productSync;
        $this->stockSync = $stockSync;

        add_action('woocommerce_api_printify_webhook', [$this, 'handleWebhook']);
        $this->registerWooCommerceWebhooks();
    }

    private function registerWooCommerceWebhooks(): void
    {
        // Register WooCommerce webhooks to Printify
        add_action('woocommerce_created_webhook', [$this, 'setupWooCommerceWebhook']);
        
        // Define topics we want to listen to
        $topics = [
            'order.created' => 'order.created',
            'order.updated' => 'order.updated',
            'product.updated' => 'product.updated',
            'product.deleted' => 'product.deleted'
        ];

        foreach ($topics as $topic => $delivery_url) {
            $this->ensureWebhookExists($topic, $delivery_url);
        }
    }

    private function ensureWebhookExists(string $topic, string $delivery_url): void
    {
        global $wpdb;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->prefix}wc_webhooks 
            WHERE topic = %s AND delivery_url LIKE %s",
            $topic,
            '%' . $wpdb->esc_like($delivery_url) . '%'
        ));

        if (!$existing) {
            $webhook = new \WC_Webhook();
            $webhook->set_name('Printify - ' . $topic);
            $webhook->set_topic($topic);
            $webhook->set_delivery_url(
                get_rest_url(null, 'printify/v1/' . $delivery_url)
            );
            $webhook->set_status('active');
            $webhook->save();
        }
    }

    public function handleWebhook(): void
    {
        try {
            $rawPayload = file_get_contents('php://input');
            $signature = $_SERVER[self::PRINTIFY_SIGNATURE_HEADER] ?? '';

            if (!$this->validateWebhookSignature($rawPayload, $signature)) {
                $this->logger->error('Invalid webhook signature');
                status_header(401);
                exit;
            }

            $payload = json_decode($rawPayload, true);
            if (!$payload || !isset($payload['event'])) {
                throw new \Exception('Invalid webhook payload');
            }

            $this->processWebhookEvent($payload);
            status_header(200);

        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
            status_header(500);
        }
    }

    private function validateWebhookSignature(string $payload, string $signature): bool
    {
        $secret = $this->config->get('webhook_secret');
        $calculated = hash_hmac('sha256', $payload, $secret);
        return hash_equals($calculated, $signature);
    }

    private function processWebhookEvent(array $payload): void
    {
        $event = $payload['event'];
        $data = $payload['data'];

        switch ($event) {
            case 'order.created':
            case 'order.updated':
                $this->handleOrderEvent($event, $data);
                break;

            case 'product.created':
            case 'product.updated':
                $this->handleProductEvent($event, $data);
                break;

            case 'stock.updated':
                $this->handleStockEvent($data);
                break;

            case 'shipping.updated':
                $this->handleShippingEvent($data);
                break;
        }

        do_action('printify_webhook_processed', $event, $data);
    }

    private function handleOrderEvent(string $event, array $data): void
    {
        $orderId = $data['external_id'] ?? null;
        if (!$orderId) {
            return;
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            return;
        }

        switch ($event) {
            case 'order.created':
                $this->orderSync->handlePrintifyOrderCreated($order, $data);
                break;

            case 'order.updated':
                $this->orderSync->handlePrintifyOrderUpdated($order, $data);
                break;
        }
    }

    private function handleProductEvent(string $event, array $data): void
    {
        $productId = $data['external_id'] ?? null;
        
        switch ($event) {
            case 'product.created':
                $this->productSync->handlePrintifyProductCreated($data);
                break;

            case 'product.updated':
                if ($productId) {
                    $this->productSync->handlePrintifyProductUpdated($productId, $data);
                }
                break;
        }
    }

    private function handleStockEvent(array $data): void
    {
        $this->stockSync->handleStockUpdate($data);
    }

    private function handleShippingEvent(array $data): void
    {
        $orderId = $data['external_id'] ?? null;
        if (!$orderId) {
            return;
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            return;
        }

        // Update tracking information
        $tracking = $data['tracking'] ?? null;
        if ($tracking) {
            $order->update_meta_data('_printify_tracking_number', $tracking['number']);
            $order->update_meta_data('_printify_tracking_url', $tracking['url']);
            $order->update_meta_data('_printify_carrier', $tracking['carrier']);
            $order->save();

            // Add order note
            $note = sprintf(
                __('Printify shipping updated - Carrier: %s, Tracking: %s', 'wp-woocommerce-printify-sync'),
                $tracking['carrier'],
                $tracking['number']
            );
            $order->add_order_note($note, true);
        }
    }
}