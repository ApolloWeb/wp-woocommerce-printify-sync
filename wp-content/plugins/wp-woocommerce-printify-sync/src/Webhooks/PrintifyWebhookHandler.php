<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

use ApolloWeb\WPWooCommercePrintifySync\Services\ProductImportService;
use ApolloWeb\WPWooCommercePrintifySync\Services\OrderSyncService;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class PrintifyWebhookHandler
{
    private ProductImportService $productImporter;
    private OrderSyncService $orderSync;
    private LoggerInterface $logger;

    public function __construct(
        ProductImportService $productImporter,
        OrderSyncService $orderSync,
        LoggerInterface $logger
    ) {
        $this->productImporter = $productImporter;
        $this->orderSync = $orderSync;
        $this->logger = $logger;
    }

    public function handleWebhook(): void
    {
        try {
            // Verify webhook signature
            $this->verifyWebhook();

            // Get webhook payload
            $payload = json_decode(file_get_contents('php://input'), true);
            $event = $_SERVER['HTTP_X_PRINTIFY_EVENT'] ?? '';

            // Process webhook
            switch ($event) {
                case 'product.created':
                case 'product.updated':
                    $this->handleProductWebhook($payload['product']);
                    break;

                case 'product.deleted':
                    $this->handleProductDeletion($payload['product']['id']);
                    break;

                case 'order.created':
                case 'order.updated':
                case 'order.cancelled':
                    $this->handleOrderWebhook($payload['order']);
                    break;

                default:
                    $this->logger->warning('Unknown webhook event', [
                        'event' => $event,
                        'payload' => $payload
                    ]);
            }

            wp_send_json_success();

        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }
    }

    private function verifyWebhook(): void
    {
        $signature = $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] ?? '';
        $payload = file_get_contents('php://input');
        $secret = get_option('printify_webhook_secret');

        $calculatedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($calculatedSignature, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }
    }

    private function handleProductWebhook(array $product): void
    {
        $this->productImporter->importProducts([$product]);
    }

    private function handleProductDeletion(string $printifyId): void
    {
        global $wpdb;
        
        $productId = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
            WHERE meta_key = '_printify_id' 
            AND meta_value = %s 
            LIMIT 1",
            $printifyId
        ));

        if ($productId) {
            wp_delete_post($productId, true);
        }
    }

    private function handleOrderWebhook(array $order): void
    {
        $this->orderSync->handlePrintifyOrder($order);
    }
}