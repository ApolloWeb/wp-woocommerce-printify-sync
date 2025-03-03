<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Webhook;

use ApolloWeb\WPWooCommercePrintifySync\Sync\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Logs\Logger;

class WebhookHandler
{
    public static function register()
    {
        add_action('woocommerce_api_printify_webhook', [__CLASS__, 'handleWebhook']);
    }

    public static function handleWebhook()
    {
        $payload = file_get_contents('php://input');
        $event = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::log('Invalid JSON payload');
            status_header(400);
            exit;
        }

        switch ($event['event']) {
            case 'product.updated':
                ProductSync::syncProducts();
                break;
            case 'order.created':
                // Handle order created event
                break;
            // Add more cases as needed...
        }

        status_header(200);
        exit;
    }
}