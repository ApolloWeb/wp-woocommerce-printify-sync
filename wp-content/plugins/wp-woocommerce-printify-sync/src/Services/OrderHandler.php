<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

class OrderHandler
{
    private ConfigService $config;
    private PrintifyAPI $printifyApi;
    private LoggerInterface $logger;

    public function __construct(
        ConfigService $config,
        PrintifyAPI $printifyApi,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->printifyApi = $printifyApi;
        $this->logger = $logger;

        $this->initHooks();
    }

    private function initHooks(): void
    {
        // Order status changes
        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChange'], 10, 4);
        
        // New order
        add_action('woocommerce_checkout_order_processed', [$this, 'handleNewOrder'], 10, 3);
        
        // Order cancellation
        add_action('woocommerce_order_cancelled', [$this, 'handleOrderCancellation']);
        
        // Order refund
        add_action('woocommerce_order_refunded', [$this, 'handleOrderRefund'], 10, 2);
    }

    public function handleNewOrder(int $orderId, array $postedData, \WC_Order $order): void
    {
        try {
            if (!$this->shouldSyncOrder($order)) {
                return;
            }

            $printifyOrder = $this->preparePrintifyOrder($order);
            $response = $this->printifyApi->createOrder($printifyOrder);

            $this->updateOrderMeta($order, [
                '_printify_order_id' => $response['id'],
                '_printify_order_status' => $response['status'],
                '_printify_sync_time' => current_time('mysql', true)
            ]);

            $this->logger->info('Order created in Printify', [
                'order_id' => $orderId,
                'printify_order_id' => $response['id']
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create Printify order', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handleOrderStatusChange(int $orderId, string $oldStatus, string $newStatus, \WC_Order $order): void
    {
        try {
            $printifyOrderId = $this->getOrderMeta($order, '_printify_order_id');
            if (!$printifyOrderId) {
                return;
            }

            $printifyStatus = $this->mapWooCommerceStatusToPrintify($newStatus);
            if ($printifyStatus) {
                $this->printifyApi->updateOrderStatus($printifyOrderId, $printifyStatus);
                
                $this->updateOrderMeta($order, [
                    '_printify_order_status' => $printifyStatus,
                    '_printify_sync_time' => current_time('mysql', true)
                ]);

                $this->logger->info('Order status updated in Printify', [
                    'order_id' => $orderId,
                    'printify_order_id' => $printifyOrderId,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to update Printify order status', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handleOrderCancellation(\WC_Order $order): void
    {
        try {
            $printifyOrderId = $this->getOrderMeta($order, '_printify_order_id');
            if (!$printifyOrderId) {
                return;
            }

            $this->printifyApi->cancelOrder($printifyOrderId);
            
            $this->updateOrderMeta($order, [
                '_printify_order_status' => 'cancelled',
                '_printify_sync_time' => current_time('mysql', true)
            ]);

            $this->logger->info('Order cancelled in Printify', [
                'order_id' => $order->get_id(),
                'printify_order_id' => $printifyOrderId
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel Printify order', [
                'order_id' => $order->get_id(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function shouldSyncOrder(\WC_Order $order): bool
    {
        $hasPrintifyProducts = false;
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->get_meta('_printify_id')) {
                $hasPrintifyProducts = true;
                break;
            }
        }

        return $hasPrintifyProducts;
    }

    private function preparePrintifyOrder(\WC_Order $order): array
    {
        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $printifyId = $product->get_meta('_printify_id');
            if (!$printifyId) continue;

            $items[] = [
                'product_id' => $printifyId,
                'quantity' => $item->get_quantity(),
                'variant_id' => $product->get_meta('_printify_variant_id'),
                'price' => $item->get_total() / $item->get_quantity()
            ];
        }

        return [
            'external_id' => $order->get_id(),
            'line_items' => $items,
            'shipping_address' => $this->formatAddress($order, 'shipping'),
            'billing_address' => $this->formatAddress($order, 'billing'),
            'shipping_method' => $order->get_shipping_method(),
            'notes' => $order->get_customer_note()
        ];
    }

    private function formatAddress(\WC_Order $order, string $type): array
    {
        $getter = "get_{$type}";
        return [
            'first_name' => $order->{$getter . '_first_name'}(),
            'last_name' => $order->{$getter . '_last_name'}(),
            'address1' => $order->{$getter . '_address_1'}(),
            'address2' => $order->{$getter . '_address_2'}(),
            'city' => $order->{$getter . '_city'}(),
            'state' => $order->{$getter . '_state'}(),
            'country' => $order->{$getter . '_country'}(),
            'zip' => $order->{$getter . '_postcode'}(),
            'phone' => $order->get_billing_phone(),
            'email' => $order->get_billing_email()
        ];
    }

    private function updateOrderMeta(\WC_Order $order, array $metadata): void
    {
        foreach ($metadata as $key => $value) {
            $order->update_meta_data($key, $value);
        }
        $order->save();
    }

    private function getOrderMeta(\WC_Order $order, string $key)
    {
        return $order->get_meta($key);
    }

    private function mapWooCommerceStatusToPrintify(string $status): ?string
    {
        $statusMap = [
            'pending' => 'draft',
            'processing' => 'pending',
            'on-hold' => 'on_hold',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'failed'
        ];

        return $statusMap[$status] ?? null;
    }
}