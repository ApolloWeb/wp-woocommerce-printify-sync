<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

class ShippingManager {
    private LoggerService $logger;
    
    public function __construct(LoggerService $logger) {
        $this->logger = $logger;
        
        // Register webhook handler
        add_action('wpwps_webhook_shipment_update', [$this, 'handleShipmentWebhook'], 10, 2);
    }

    public function handleShipmentWebhook(string $event_type, array $event_data): void {
        $shipping_statuses = [
            'shipping.created' => 'created',
            'shipping.pending' => 'pending', 
            'shipping.in_transit' => 'in_transit',
            'shipping.delivered' => 'delivered',
            'shipping.failed' => 'failed',
            'shipping.exception' => 'exception'
        ];

        $status = $shipping_statuses[$event_type] ?? null;
        
        if (!$status) {
            $this->logger->warning('Unknown shipping event type', ['type' => $event_type]);
            return;
        }

        $order_id = $event_data['order_id'] ?? null;
        if (!$order_id) {
            $this->logger->error('Missing order ID in shipping webhook', ['data' => $event_data]);
            return;
        }

        $this->updateShippingStatus($order_id, $status, $event_data);
    }
}
