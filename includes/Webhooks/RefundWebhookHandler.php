<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

class RefundWebhookHandler {
    public function __construct() {
        add_action('woocommerce_order_refunded', [$this, 'handleOrderRefund'], 10, 2);
    }

    public function handleOrderRefund($order_id, $refund_id) {
        // Code to handle order refund webhook
    }
}