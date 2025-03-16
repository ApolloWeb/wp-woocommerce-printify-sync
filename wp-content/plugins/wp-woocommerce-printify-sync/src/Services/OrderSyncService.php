<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

class OrderSyncService
{
    private $context;
    private $logger;

    public function __construct(SyncContext $context, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->logger = $logger;
    }

    public function syncOrder(int $orderId): void
    {
        try {
            // Get order using HPOS if available
            $order = self::isHPOSEnabled() 
                ? wc_get_order($orderId) 
                : new \WC_Order($orderId);

            if (!$order) {
                throw new \Exception("Order not found: {$orderId}");
            }

            // Prepare order data for Printify
            $printifyOrder = $this->preparePrintifyOrder($order);

            // Send to Printify
            $this->sendOrderToPrintify($printifyOrder);

            // Update order meta
            $order->update_meta_data('_printify_sync_status', 'synced');
            $order->update_meta_data('_printify_last_sync', $this->context->getCurrentTime());
            $order->save();

            $this->logger->info('Order synced successfully', [
                'order_id' => $orderId,
                'sync_time' => $this->context->getCurrentTime()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Order sync failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getOrdersByStatus(string $status): array
    {
        if (self::isHPOSEnabled()) {
            global $wpdb;
            $orderTable = OrderUtil::get_orders_table_name();
            
            return $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM {$orderTable} WHERE status = %s",
                $status
            ));
        } else {
            return wc_get_orders([
                'status' => $status,
                'return' => 'ids',
            ]);
        }
    }

    private function preparePrintifyOrder(\WC_Order $order): array
    {
        // Prepare order data
        $orderData = [
            'external_id' => $order->get_id(),
            'line_items' => [],
            'shipping_address' => $this->getShippingAddress($order),
            'billing_address' => $this->getBillingAddress($order),
        ];

        // Get line items
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $printifyId = $product->get_meta('_printify_id');
            if (!$printifyId) continue;

            $orderData['line_items'][] = [
                'product_id' => $printifyId,
                'quantity' => $item->get_quantity(),
                'variant_id' => $product->get_meta('_printify_variant_id'),
            ];
        }

        return $orderData;
    }

    public static function isHPOSEnabled(): bool
    {
        return class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class) && 
               \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }
}