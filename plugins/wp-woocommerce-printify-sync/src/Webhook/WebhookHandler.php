<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhook;

use WP_REST_Request;
use WP_REST_Response;
use ApolloWeb\WPWooCommercePrintifySync\Product\ProductImportQueue;

class WebhookHandler {
    /**
     * Register REST API endpoints.
     */
    public function register(): void {
        add_action('rest_api_init', [$this, 'registerEndpoints']);
    }

    /**
     * Register our custom endpoints.
     */
    public function registerEndpoints(): void {
        register_rest_route(
            'wp-woocommerce-printify-sync/v1',
            '/webhook',
            [
                'methods' => 'POST',
                'callback' => [$this, 'handleWebhook'],
                'permission_callback' => '__return_true'
            ]
        );
        register_rest_route(
            'wp-woocommerce-printify-sync/v1',
            '/order',
            [
                'methods' => 'POST',
                'callback' => [$this, 'handleOrderWebhook'],
                'permission_callback' => '__return_true'
            ]
        );
        register_rest_route(
            'wp-woocommerce-printify-sync/v1',
            '/update-product',
            [
                'methods' => 'POST',
                'callback' => [$this, 'handleProductUpdate'],
                'permission_callback' => '__return_true'
            ]
        );
    }

    /**
     * Handle Printify webhook for products.
     */
    public function handleWebhook(WP_REST_Request $request): WP_REST_Response {
        $payload = $request->get_json_params();
        if (empty($payload['products']) || !is_array($payload['products'])) {
            return new WP_REST_Response(['message' => 'No products found.'], 400);
        }
        $importQueue = new ProductImportQueue();
        $importQueue->scheduleImport($payload['products']);
        return new WP_REST_Response(['message' => 'Product import scheduled successfully.'], 200);
    }

    /**
     * Handle Printify order webhook (phase 2).
     */
    public function handleOrderWebhook(WP_REST_Request $request): WP_REST_Response {
        $orderData = $request->get_json_params();
        // ...existing code to process order data...
        return new WP_REST_Response(['message' => 'Order webhook received.'], 200);
    }

    /**
     * Handle WooCommerce ➔ Printify product updates.
     */
    public function handleProductUpdate(WP_REST_Request $request): WP_REST_Response {
        $data = $request->get_json_params();
        if (empty($data['external_product_id'])) {
            return new WP_REST_Response(['message' => 'Missing external_product_id.'], 400);
        }
        // ...existing code to update product via Printify API...
        return new WP_REST_Response(['message' => 'Product update sent to Printify.'], 200);
    }
}
