<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

class OrderWebhookHandler {
    public function __construct() {
        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChange'], 10, 4);
    }

    public function handleOrderStatusChange($order_id, $old_status, $new_status, $order) {
        // Code to handle order status change webhook
    }
}