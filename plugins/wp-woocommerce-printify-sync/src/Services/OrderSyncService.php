<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class OrderSyncService {
    private PrintifyAPIClient $api;
    private WebhookHandler $webhookHandler;
    
    public function createPrintifyOrder(\WC_Order $order): void {
        try {
            $printifyOrder = $this->preparePrintifyOrder($order);
            $response = $this->api->createOrder($printifyOrder);
            
            $order->update_meta_data('_printify_order_id', $response['id']);
            $order->save();
            
            $this->webhookHandler->registerOrderWebhooks($response['id']);
            
        } catch (\Exception $e) {
            $this->handleOrderError($order, $e);
        }
    }
    
    private function preparePrintifyOrder(\WC_Order $order): array {
        return [
            'external_id' => $order->get_id(),
            'line_items' => $this->getLineItems($order),
            'shipping_address' => $this->getShippingAddress($order),
            'shipping_method' => $this->getShippingMethod($order)
        ];
    }
}