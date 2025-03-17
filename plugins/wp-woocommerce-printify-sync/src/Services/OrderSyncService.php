<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class OrderSyncService extends AbstractService
{
    private PrintifyAPI $api;
    private OrderStatusManager $statusManager;

    public function __construct(
        PrintifyAPI $api,
        OrderStatusManager $statusManager,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        parent::__construct($logger, $config);
        $this->api = $api;
        $this->statusManager = $statusManager;
    }

    public function handlePrintifyOrder(array $printifyOrder): void
    {
        try {
            $orderId = $this->getWooCommerceOrderId($printifyOrder['id']);
            if (!$orderId) {
                $this->logOperation('handlePrintifyOrder', [
                    'message' => 'No matching WooCommerce order found',
                    'printify_order_id' => $printifyOrder['id']
                ]);
                return;
            }

            $order = wc_get_order($orderId);
            if (!$order) {
                throw new \Exception("WooCommerce order not found: {$orderId}");
            }

            // Update order status
            $this->statusManager->updateOrderStatus($order, $printifyOrder['status']);

            // Update shipping information if available
            if (!empty($printifyOrder['shipment'])) {
                $this->updateShippingInfo($order, $printifyOrder['shipment']);
            }

            // Update Printify metadata
            $this->updatePrintifyMetadata($order, $printifyOrder);

            $this->logOperation('handlePrintifyOrder', [
                'order_id' => $orderId,
                'printify_order_id' => $printifyOrder['id'],
                'status' => $printifyOrder['status']
            ]);

        } catch (\Exception $e) {
            $this->logError('handlePrintifyOrder', $e, [
                'printify_order' => $printifyOrder
            ]);
        }
    }

    private function updateShippingInfo(\WC_Order $order, array $shipment): void
    {
        $order->update_meta_data('_tracking_provider', $shipment['carrier']);
        $order->update_meta_data('_tracking_number', $shipment['tracking_number']);
        $order->update_meta_data('_tracking_url', $shipment['tracking_url']);
        
        // Add shipping note
        $order->add_order_note(
            sprintf(
                __('Shipping updated by Printify - Carrier: %s, Tracking: %s', 'wp-woocommerce-printify-sync'),
                $shipment['carrier'],
                $shipment['tracking_number']
            ),
            true // Customer note
        );
    }

    private function updatePrintifyMetadata(\WC_Order $order, array $printifyOrder): void
    {
        $order->update_meta_data('_printify_status', $printifyOrder['status']);
        $order->update_meta_data('_printify_last_sync', $this->getCurrentTime());
        $order->update_meta_data('_printify_order_data', [
            'line_items' => $printifyOrder['line_items'],
            'shipping_method' => $printifyOrder['shipping_method'],
            'shipping_cost' => $printifyOrder['shipping_cost'],
            'total_cost' => $printifyOrder['total_cost'],
            'created_at' => $printifyOrder['created_at'],
            'updated_at' => $printifyOrder['updated_at']
        ]);
        $order->save();
    }
}