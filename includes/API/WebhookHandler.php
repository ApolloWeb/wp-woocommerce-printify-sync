<?php
/**
 * Handles Printify webhooks.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Utilities\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Sync\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Sync\OrderSync;

class WebhookHandler {

    public function register_webhook_endpoint() {
        register_rest_route('wpwps/v1', '/webhook', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'process_webhook'),
            'permission_callback' => '__return_true',
        ));
    }

    public function register_printify_webhooks($api_key) {
        $api_client = new APIClient($api_key);
        $webhook_url = rest_url('wpwps/v1/webhook');

        $existing_webhooks = $api_client->get_webhooks();
        if (is_array($existing_webhooks)) {
            foreach ($existing_webhooks as $webhook) {
                if (isset($webhook['id']) && strpos($webhook['url'], rest_url('wpwps/v1')) !== false) {
                    $api_client->delete_webhook($webhook['id']);
                }
            }
        }

        $events = array(
            'product.created',
            'product.updated',
            'order.created',
            'order.updated'
        );
        $results = array();
        $success = true;

        foreach ($events as $event) {
            $result = $api_client->register_webhook($webhook_url, $event);
            if ($result === false) {
                $success = false;
                $results[] = "Failed to register webhook for {$event}";
            } else {
                $results[] = "Registered webhook for {$event}";
            }
        }

        return array(
            'success' => $success,
            'message' => implode(', ', $results),
        );
    }

    public function process_webhook($request) {
        $payload = $request->get_body();
        $data = json_decode($payload, true);
        Logger::log('Webhook', 'Received webhook event: ' . $payload, 'info');

        if (isset($data['event'])) {
            switch ($data['event']) {
                case 'product.created':
                case 'product.updated':
                    $product_sync = new ProductSync();
                    $product_sync->process_webhook($data);
                    break;
                case 'order.created':
                case 'order.updated':
                    $order_sync = new OrderSync();
                    $order_sync->process_webhook($data);
                    break;
                default:
                    Logger::log('Webhook', 'Unhandled webhook event: ' . $data['event'], 'warning');
                    break;
            }
        } else {
            Logger::log('Webhook', 'Invalid webhook payload received.', 'error');
        }
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Webhook processed.',
        ));
    }
}