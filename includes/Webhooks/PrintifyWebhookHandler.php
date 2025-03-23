<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

class PrintifyWebhookHandler {
    // ...existing code...

    private $events = [
        // Order Events
        'order.created' => 'handleOrderCreated',
        'order.updated' => 'handleOrderUpdated',
        'order.canceled' => 'handleOrderCanceled',
        'order.hold' => 'handleOrderHold',
        'order.failed' => 'handleOrderFailed',
        
        // Shipping Events
        'shipping.update' => 'handleShippingUpdate',
        'fulfillment.created' => 'handleFulfillmentCreated',
        'fulfillment.failed' => 'handleFulfillmentFailed',
        
        // Product Events
        'product.published' => 'handleProductPublished',
        'product.unpublished' => 'handleProductUnpublished',
        'product.updated' => 'handleProductUpdated',
        'product.deleted' => 'handleProductDeleted',
        
        // Shop Events
        'shop.disconnected' => 'handleShopDisconnected'
    ];

    protected function verifySignature(string $payload, string $signature): bool {
        $calculated = hash_hmac('sha256', $payload, $this->settings->get('webhook_secret'));
        return hash_equals($calculated, $signature);
    }

    // ...existing code...
}
