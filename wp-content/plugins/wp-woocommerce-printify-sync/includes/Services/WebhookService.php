<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class WebhookService {
    private $api_service;
    private $order_service;
    private $product_service;
    private $email_service;

    public function __construct() {
        $this->api_service = new ApiService();
        $this->order_service = new OrderService();
        $this->product_service = new ProductService();
        $this->email_service = new EmailService();

        add_action('init', [$this, 'registerWebhookEndpoint']);
        add_action('wpwps_register_webhooks', [$this, 'registerWebhooks']);
    }

    public function registerWebhookEndpoint(): void {
        add_rewrite_rule(
            'wpwps-webhook/?$',
            'index.php?wpwps-webhook=true',
            'top'
        );

        add_filter('query_vars', function($vars) {
            $vars[] = 'wpwps-webhook';
            return $vars;
        });

        add_action('parse_request', [$this, 'handleWebhook']);
    }

    public function registerWebhooks(): void {
        $webhook_url = home_url('wpwps-webhook');
        
        $events = [
            'order.created',
            'order.updated',
            'order.canceled',
            'order.fulfilled',
            'product.created',
            'product.updated',
            'product.deleted',
            'shipping.updated'
        ];

        $response = $this->api_service->createWebhook($webhook_url, $events);
        
        if (!$response['success']) {
            do_action('wpwps_log_error', 'Failed to register webhooks', $response);
        } else {
            update_option('wpwps_webhook_id', $response['data']['id']);
            do_action('wpwps_log_info', 'Webhooks registered successfully', [
                'webhook_id' => $response['data']['id'],
                'url' => $webhook_url
            ]);
        }
    }

    public function handleWebhook(\WP {
        if (!isset($wp->query_vars['wpwps-webhook'])) {
            return;
        }

        // Verify signature
        if (!$this->verifyWebhookSignature()) {
            status_header(401);
            die('Invalid signature');
        }

        // Get webhook data
        $payload = json_decode(file_get_contents('php://input'), true);
        
        if (!$payload || !isset($payload['event'])) {
            status_header(400);
            die('Invalid payload');
        }

        try {
            $this->processWebhook($payload);
            status_header(200);
            die('OK');
        } catch (\Exception $e) {
            do_action('wpwps_log_error', 'Webhook processing failed', [
                'event' => $payload['event'],
                'error' => $e->getMessage()
            ]);
            status_header(500);
            die('Processing failed');
        }
    }

    private function verifyWebhookSignature(): bool {
        if (!isset($_SERVER['HTTP_X_PRINTIFY_SIGNATURE'])) {
            return false;
        }

        $signature = $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'];
        $payload = file_get_contents('php://input');
        $secret = get_option('wpwps_webhook_secret');

        $expected = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($expected, $signature);
    }

    private function processWebhook(array $payload): void {
        $event = $payload['event'];
        $data = $payload['data'];

        switch ($event) {
            case 'order.created':
            case 'order.updated':
                $this->handleOrderUpdate($data);
                break;

            case 'order.canceled':
                $this->handleOrderCancellation($data);
                break;

            case 'order.fulfilled':
                $this->handleOrderFulfillment($data);
                break;

            case 'product.created':
            case 'product.updated':
                $this->handleProductUpdate($data);
                break;

            case 'product.deleted':
                $this->handleProductDeletion($data);
                break;

            case 'shipping.updated':
                $this->handleShippingUpdate($data);
                break;

            default:
                throw new \Exception('Unknown webhook event: ' . $event);
        }

        do_action('wpwps_log_info', 'Webhook processed', [
            'event' => $event,
            'data_id' => $data['id'] ?? null
        ]);
    }

    private function handleOrderUpdate(array $data): void {
        // Find WooCommerce order
        $order_id = $this->getOrderIdByPrintifyId($data['id']);
        
        if (!$order_id) {
            return; // No local order found
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Update order status
        if (isset($data['status'])) {
            $wc_status = $this->order_service->mapOrderStatus($data['status']);
            $order->update_status($wc_status);
        }

        // Update tracking info
        if (!empty($data['tracking'])) {
            update_post_meta($order_id, '_printify_tracking_number', $data['tracking']['number']);
            update_post_meta($order_id, '_printify_tracking_url', $data['tracking']['url']);
            update_post_meta($order_id, '_printify_carrier', $data['tracking']['carrier']);

            // Notify customer
            $this->email_service->queueEmail(
                $order->get_billing_email(),
                sprintf(__('Order #%s Tracking Update', 'wp-woocommerce-printify-sync'), $order->get_order_number()),
                sprintf(
                    __('Your order has been shipped! Track your package here: %s', 'wp-woocommerce-printify-sync'),
                    $data['tracking']['url']
                )
            );
        }
    }

    private function handleOrderCancellation(array $data): void {
        $order_id = $this->getOrderIdByPrintifyId($data['id']);
        
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $order->update_status('cancelled', __('Order cancelled by Printify', 'wp-woocommerce-printify-sync'));

        // Notify customer
        $this->email_service->queueEmail(
            $order->get_billing_email(),
            sprintf(__('Order #%s Cancelled', 'wp-woocommerce-printify-sync'), $order->get_order_number()),
            sprintf(
                __('Unfortunately, your order has been cancelled. Reason: %s', 'wp-woocommerce-printify-sync'),
                $data['reason'] ?? __('No reason provided', 'wp-woocommerce-printify-sync')
            )
        );
    }

    private function handleOrderFulfillment(array $data): void {
        $order_id = $this->getOrderIdByPrintifyId($data['id']);
        
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $order->update_status('completed');

        // Send fulfillment notification
        $this->email_service->queueEmail(
            $order->get_billing_email(),
            sprintf(__('Order #%s Fulfilled', 'wp-woocommerce-printify-sync'), $order->get_order_number()),
            __('Your order has been fulfilled and is on its way!', 'wp-woocommerce-printify-sync')
        );
    }

    private function handleProductUpdate(array $data): void {
        $this->product_service->syncProduct($data);
    }

    private function handleProductDeletion(array $data): void {
        $product_id = $this->getProductIdByPrintifyId($data['id']);
        
        if (!$product_id) {
            return;
        }

        wp_delete_post($product_id, true);
    }

    private function handleShippingUpdate(array $data): void {
        // Trigger shipping profiles sync
        do_action('wpwps_sync_shipping_profiles');
    }

    private function getOrderIdByPrintifyId(string $printify_id): ?int {
        global $wpdb;
        
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_order_id' 
            AND meta_value = %s 
            LIMIT 1",
            $printify_id
        ));
        
        return $order_id ? (int) $order_id : null;
    }

    private function getProductIdByPrintifyId(string $printify_id): ?int {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' 
            AND meta_value = %s 
            LIMIT 1",
            $printify_id
        ));
        
        return $product_id ? (int) $product_id : null;
    }
}