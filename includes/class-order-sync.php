<?php

namespace ApolloWeb\WooCommercePrintifySync;

class OrderSync
{
    public function __construct()
    {
        add_action('woocommerce_thankyou', [$this, 'syncOrderToPrintify']);
    }

    public function syncOrderToPrintify($orderId)
    {
        $order = wc_get_order($orderId);
        // Implementation to sync order to Printify.
    }

    public function registerPrintifyWebhook()
    {
        // Implementation to register Printify webhook.
    }
}