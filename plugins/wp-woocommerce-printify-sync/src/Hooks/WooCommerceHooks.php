<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Hooks;

use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Services\OrderSyncService;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class WooCommerceHooks
{
    private PrintifyAPI $api;
    private OrderSyncService $orderSync;
    private LoggerInterface $logger;

    public function __construct(
        PrintifyAPI $api,
        OrderSyncService $orderSync,
        LoggerInterface $logger
    ) {
        $this->api = $api;
        $this->orderSync = $orderSync;
        $this->logger = $logger;

        $this->registerHooks();
    }

    private function registerHooks(): void
    {
        // Order hooks
        add_action('woocommerce_checkout_order_processed', [$this, 'handleNewOrder'], 10, 3);
        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChange'], 10, 4);
        add_action('woocommerce_order_refunded', [$this, 'handleOrderRefund'], 10, 2);

        // Product hooks
        add_action('woocommerce_update_product', [$this, 'handleProductUpdate'], 10, 2);
        add_action('before_delete_post', [$this, 'handleProductDeletion'], 10, 1);
    }

    public function handleNewOrder(int $orderId, array $postedData, \WC_Order $order): void
    {
        try {
            // Check if order contains Printify products
            if (!$this->orderContainsPrintifyProducts($order)) {
                return;
            }

            // Create order in Printify
            $this->orderSync->createPrintifyOrder($order);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create Printify order', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handleOrderStatusChange(
        int $orderId,
        string $oldStatus,
        string $newStatus,
        \WC_Order $order
    ): void {
        try {
            $printifyOrderId = $order->get_meta('_printify_order_id');
            if (!$printifyOrderId) {
                return;
            }

            // Map status to Printify status
            $printifyStatus = $this->mapWooCommerceStatus($newStatus);
            if ($printifyStatus) {
                $this->api->updateOrder($printifyOrderId, [
                    'status' => $printifyStatus
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to update Printify order status', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handleOrderRefund(int $orderId, int $refundId): void
    {
        try {
            $order = wc_get_order($orderId);
            if (!$order) {
                return;
            }

            $printifyOrderId = $order->get_meta('_printify_order_id');
            if (!$printifyOrderId) {
                return;
            }

            // Process refund in Printify
            $this->api->cancelOrder($printifyOrderId);

        } catch (\Exception $e) {
            $this->logger->error('Failed to process Printify order refund', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handleProductUpdate(int $productId, \WC_Product $product): void
    {
        try {
            $printifyId = get_post_meta($productId, '_printify_id', true);
            if (!$printifyId) {
                return;
            }

            // Update product in Printify
            $this->api->updateProduct($printifyId, [
                'title' => $product->get_name(),
                'description' => $product->get_description(),
                'price' => $product->get_regular_price(),
                // Add other fields as needed
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update Printify product', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handleProductDeletion(int $postId): void
    {
        if (get_post_type($postId) !== 'product') {
            return;
        }

        try {
            $printifyId = get_post_meta($postId, '_printify_id', true);
            if (!$printifyId) {
                return;
            }

            // Delete product in Printify
            $this->api->deleteProduct($printifyId);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete Printify product', [
                'product_id' => $postId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function orderContainsPrintifyProducts(\WC_Order $order): bool
    {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            if (get_post_meta($product->get_id(), '_printify_id', true)) {
                return true;
            }
        }
        return false;
    }

    private function mapWooCommerceStatus(string $status): ?string
    {
        $statusMap = [
            'pending' => 'draft',
            'processing' => 'pending',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'cancelled',
            'failed' => 'cancelled',
            'on-hold' => 'pending'
        ];

        return $statusMap[$status] ?? null;
    }
}