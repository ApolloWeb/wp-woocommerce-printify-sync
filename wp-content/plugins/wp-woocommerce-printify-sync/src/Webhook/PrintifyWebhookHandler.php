<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Webhook;

class PrintifyWebhookHandler
{
    private $secret_key;
    private $integration;

    public function __construct($integration)
    {
        $this->secret_key = get_option('wpwps_printify_webhook_secret');
        $this->integration = $integration;
        
        add_action('woocommerce_api_printify_webhook', [$this, 'handleWebhook']);
    }

    public function handleWebhook(): void
    {
        // Verify webhook signature
        if (!$this->verifyWebhookSignature()) {
            status_header(401);
            exit('Invalid signature');
        }

        // Get webhook payload
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            status_header(400);
            exit('Invalid payload');
        }

        // Process webhook event
        try {
            $this->processWebhookEvent($data);
            status_header(200);
            exit('OK');
        } catch (\Exception $e) {
            status_header(500);
            exit($e->getMessage());
        }
    }

    private function processWebhookEvent(array $data): void
    {
        $event_type = $data['type'] ?? '';
        $event_data = $data['data'] ?? [];

        switch ($event_type) {
            case 'order.created':
                $this->handleOrderCreated($event_data);
                break;
            
            case 'order.updated':
                $this->handleOrderUpdated($event_data);
                break;

            case 'fulfillment.created':
                $this->handleFulfillmentCreated($event_data);
                break;

            case 'fulfillment.updated':
                $this->handleFulfillmentUpdated($event_data);
                break;

            case 'product.published':
                $this->handleProductPublished($event_data);
                break;

            case 'product.updated':
                $this->handleProductUpdated($event_data);
                break;

            case 'product.deleted':
                $this->handleProductDeleted($event_data);
                break;

            default:
                throw new \Exception('Unknown webhook event type');
        }

        // Log webhook event
        $this->logWebhookEvent($event_type, $event_data);
    }

    private function verifyWebhookSignature(): bool
    {
        if (empty($this->secret_key)) {
            return false;
        }

        $signature = $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] ?? '';
        $payload = file_get_contents('php://input');
        
        $expected = hash_hmac('sha256', $payload, $this->secret_key);
        
        return hash_equals($expected, $signature);
    }

    private function handleOrderCreated(array $data): void
    {
        $order_id = $data['external_id'] ?? 0;
        $printify_id = $data['id'] ?? '';

        if (!$order_id || !$printify_id) {
            throw new \Exception('Invalid order data');
        }

        do_action('wpwps_printify_order_submitted', $order_id, [
            'printify_id' => $printify_id,
            'status' => $data['status'] ?? '',
            'line_items' => $data['line_items'] ?? [],
        ]);
    }

    private function handleOrderUpdated(array $data): void
    {
        $order_id = $data['external_id'] ?? 0;
        $new_status = $data['status'] ?? '';

        if (!$order_id || !$new_status) {
            throw new \Exception('Invalid order update data');
        }

        do_action('wpwps_printify_order_status_changed', $order_id, [
            'status' => $new_status,
            'previous_status' => get_post_meta($order_id, '_printify_status', true),
            'updated_at' => current_time('mysql'),
        ]);
    }

    private function handleFulfillmentCreated(array $data): void
    {
        $order_id = $this->getOrderIdFromPrintifyId($data['order_id'] ?? '');
        
        if (!$order_id) {
            throw new \Exception('Order not found');
        }

        do_action('wpwps_printify_fulfillment_started', $order_id, [
            'fulfillment_id' => $data['id'] ?? '',
            'status' => $data['status'] ?? '',
            'provider' => $data['provider'] ?? '',
        ]);
    }

    private function handleFulfillmentUpdated(array $data): void
    {
        $order_id = $this->getOrderIdFromPrintifyId($data['order_id'] ?? '');
        
        if (!$order_id) {
            throw new \Exception('Order not found');
        }

        $status = $data['status'] ?? '';
        
        if ($status === 'completed') {
            do_action('wpwps_printify_fulfillment_completed', $order_id, [
                'tracking' => $data['tracking'] ?? [],
                'completed_at' => current_time('mysql'),
            ]);
        } elseif ($status === 'failed') {
            do_action('wpwps_printify_fulfillment_failed', $order_id, [
                'error' => $data['error'] ?? '',
                'failed_at' => current_time('mysql'),
            ]);
        }
    }

    private function handleProductPublished(array $data): void
    {
        do_action('wpwps_printify_product_published', $data['id'], [
            'title' => $data['title'] ?? '',
            'variants' => $data['variants'] ?? [],
            'published_at' => current_time('mysql'),
        ]);
    }

    private function handleProductUpdated(array $data): void
    {
        $product_id = $this->getProductIdFromPrintifyId($data['id'] ?? '');
        
        if (!$product_id) {
            throw new \Exception('Product not found');
        }

        do_action('wpwps_printify_product_updated', $product_id, [
            'title' => $data['title'] ?? '',
            'variants' => $data['variants'] ?? [],
            'updated_at' => current_time('mysql'),
        ]);
    }

    private function handleProductDeleted(array $data): void
    {
        $product_id = $this->getProductIdFromPrintifyId($data['id'] ?? '');
        
        if ($product_id) {
            do_action('wpwps_printify_product_deleted', $product_id, [
                'printify_id' => $data['id'],
                'deleted_at' => current_time('mysql'),
            ]);
        }
    }

    private function getOrderIdFromPrintifyId(string $printify_id): ?int
    {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}wpwps_fulfillment_tracking 
            WHERE printify_id = %s 
            LIMIT 1",
            $printify_id
        ));
    }

    private function getProductIdFromPrintifyId(string $printify_id): ?int
    {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT product_id FROM {$wpdb->prefix}wpwps_product_sync_status 
            WHERE printify_id = %s 
            LIMIT 1",
            $printify_id
        ));
    }

    private function logWebhookEvent(string $event_type, array $event_data): void
    {
        global $wpdb;
        
        $wpdb->insert(
            "{$wpdb->prefix}wpwps_webhook_log",
            [
                'event_type' => $event_type,
                'event_data' => wp_json_encode($event_data),
                'created_at' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
            ],
            ['%s', '%s', '%s', '%s']
        );
    }
}