<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class WebhookHandler extends AbstractService
{
    private ProductImportService $productImporter;
    private OrderSyncService $orderSync;

    public function __construct(
        ProductImportService $productImporter,
        OrderSyncService $orderSync,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        parent::__construct($logger, $config);
        $this->productImporter = $productImporter;
        $this->orderSync = $orderSync;
    }

    public function handleWebhook(string $event, array $payload): void
    {
        try {
            $this->logOperation('handleWebhook', [
                'event' => $event,
                'payload' => $payload
            ]);

            match ($event) {
                'product.created' => $this->handleProductCreated($payload),
                'product.updated' => $this->handleProductUpdated($payload),
                'product.deleted' => $this->handleProductDeleted($payload),
                'order.created' => $this->handleOrderCreated($payload),
                'order.updated' => $this->handleOrderUpdated($payload),
                'order.cancelled' => $this->handleOrderCancelled($payload),
                'shipping.updated' => $this->handleShippingUpdated($payload),
                default => $this->logOperation('handleWebhook', [
                    'message' => 'Unhandled webhook event',
                    'event' => $event
                ])
            };

        } catch (\Exception $e) {
            $this->logError('handleWebhook', $e, [
                'event' => $event,
                'payload' => $payload
            ]);

            // Queue for retry if needed
            $this->queueForRetry($event, $payload);
        }
    }

    private function handleProductCreated(array $payload): void
    {
        $this->productImporter->importProducts([$payload['product']]);
    }

    private function handleProductUpdated(array $payload): void
    {
        $productId = $this->getWooCommerceProductId($payload['product']['id']);
        if ($productId) {
            $this->productImporter->updateProduct($productId, $payload['product']);
        }
    }

    private function handleProductDeleted(array $payload): void
    {
        $productId = $this->getWooCommerceProductId($payload['product']['id']);
        if ($productId) {
            $this->productImporter->deleteProduct($productId);
        }
    }

    private function handleOrderCreated(array $payload): void
    {
        $this->orderSync->handlePrintifyOrder($payload['order']);
    }

    private function handleOrderUpdated(array $payload): void
    {
        $this->orderSync->updateOrderStatus($payload['order']);
    }

    private function handleOrderCancelled(array $payload): void
    {
        $this->orderSync->cancelOrder($payload['order']);
    }

    private function handleShippingUpdated(array $payload): void
    {
        // Implementation for shipping updates
    }

    private function getWooCommerceProductId(string $printifyId): ?int
    {
        global $wpdb;
        
        $productId = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
            WHERE meta_key = '_printify_id' 
            AND meta_value = %s 
            LIMIT 1",
            $printifyId
        ));

        return $productId ? (int)$productId : null;
    }

    private function queueForRetry(string $event, array $payload): void
    {
        as_schedule_single_action(
            time() + 300, // 5 minutes delay
            'wpwps_retry_webhook',
            [
                'event' => $event,
                'payload' => $payload,
                'attempt' => ($payload['attempt'] ?? 0) + 1
            ],
            'printify-webhooks'
        );
    }
}