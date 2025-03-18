<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API;

class WebhookEndpoints {
    const WEBHOOK_SECRET = 'wpwps_webhook_secret';

    public function register() {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints() {
        register_rest_route('wpwps/v1', '/webhook/products', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_product_webhook'],
            'permission_callback' => [$this, 'verify_webhook'],
        ]);

        register_rest_route('wpwps/v1', '/webhook/orders', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_order_webhook'],
            'permission_callback' => [$this, 'verify_webhook'],
        ]);
    }

    public function verify_webhook(\WP_REST_Request $request) {
        $signature = $request->get_header('X-Printify-Signature');
        if (!$signature) {
            return false;
        }

        $payload = $request->get_body();
        $expected = hash_hmac('sha256', $payload, get_option(self::WEBHOOK_SECRET));

        return hash_equals($expected, $signature);
    }

    public function handle_product_webhook(\WP_REST_Request $request) {
        $payload = $request->get_json_params();
        $event = $request->get_header('X-Printify-Event');

        switch ($event) {
            case 'product.update':
                // Handle product update
                break;
            case 'product.delete':
                // Handle product deletion
                break;
        }

        return rest_ensure_response(['status' => 'success']);
    }

    public function handle_order_webhook(\WP_REST_Request $request) {
        $payload = $request->get_json_params();
        $event = $request->get_header('X-Printify-Event');

        switch ($event) {
            case 'order.created':
                // Handle order creation
                break;
            case 'order.status_update':
                // Handle order status update
                break;
        }

        return rest_ensure_response(['status' => 'success']);
    }
}
