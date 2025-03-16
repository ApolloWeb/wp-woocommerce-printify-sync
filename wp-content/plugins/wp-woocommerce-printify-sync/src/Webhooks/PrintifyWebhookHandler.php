<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

use ApolloWeb\WPWooCommercePrintifySync\Services\{
    ConfigService,
    LoggerInterface,
    OrderHandler
};

class PrintifyWebhookHandler
{
    private const WEBHOOK_SECRET_OPTION = 'wpwps_webhook_secret';
    private ConfigService $config;
    private LoggerInterface $logger;
    private OrderHandler $orderHandler;

    public function __construct(
        ConfigService $config,
        LoggerInterface $logger,
        OrderHandler $orderHandler
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->orderHandler = $orderHandler;

        $this->initHooks();
    }

    private function initHooks(): void
    {
        add_action('rest_api_init', [$this, 'registerEndpoints']);
    }

    public function registerEndpoints(): void
    {
        register_rest_route('wpwps/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handleWebhook'],
            'permission_callback' => [$this, 'verifyWebhook']
        ]);
    }

    public function verifyWebhook(\WP_REST_Request $request): bool
    {
        $signature = $request->get_header('X-Printify-Signature');
        if (!$signature) {
            $this->logger->error('Missing webhook signature');
            return false;
        }

        $secret = get_option(self::WEBHOOK_SECRET_OPTION);
        $payload = $request->get_body();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            $this->logger->error('Invalid webhook signature');
            return false;
        }

        return true;
    }

    public function handleWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $payload = $request->get_json_params();
            $event = $payload['event'] ?? '';
            $data = $payload['data'] ?? [];

            $this->logger->info('Received webhook', [
                'event' => $event,
                'timestamp' => current_time('mysql', true)
            ]);

            switch ($event) {
                case 'order.status_updated':
                    $this->handleOrderStatusUpdate($data);
                    break;

                case 'order.shipped':
                    $this->handleOrderShipped($data);
                    break;

                case 'order.delivered':
                    $this->handleOrderDelivered($data);
                    break;

                case 'order.cancelled':
                    $this->handleOrderCancelled($data);
                    break;

                default:
                    throw new \Exception("Unsupported webhook event: {$event}");
            }

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'event' => $event ?? 'unknown'
            ]);

            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function handleOrderStatusUpdate(array $data): void
    {
        $externalId = $data['external_id'] ?? null;
        if (!$externalId) {
            throw new \Exception('Missing external order ID');
        }

        $order = wc_get_order($externalId);
        if (!$order) {
            throw new \Exception("Order not found: {$externalId}");
        }

        $status = $data['status'] ?? '';
        $wcStatus = $this->mapPrintifyStatusToWooCommerce($status);

        if ($wcStatus && $wcStatus !== $order->get_status()) {
            $order->update_status(
                $wcStatus,
                sprintf(
                    __('Order status updated by Printify to: %s', 'wp-woocommerce-printify-sync'),
                    $status
                )
            );
        }

        $order->update_meta_data('_printify_status', $status);
        $order->update_meta_data('_printify_status_updated', current_time('mysql', true));
        $order->save();
    }

    private function handleOrderShipped(array $data): void
    {
        $externalId = $data['external_id'] ?? null;
        if (!$externalId) {
            throw new \Exception('Missing external order ID');
        }

        $order = wc_get_order($externalId);
        if (!$order) {
            throw new \Exception("Order not found: {$externalId}");
        }

        // Update tracking information
        $tracking = $data['tracking'] ?? [];
        if ($tracking) {
            $order->update_meta_data('_printify_tracking_number', $tracking['number'] ?? '');
            $order->update_meta_data('_printify_tracking_carrier', $tracking['carrier'] ?? '');
            $order->update_meta_data('_printify_tracking_url', $tracking['url'] ?? '');
        }

        // Update order status
        if ($order->get_status() === 'processing') {
            $order->update_status(
                'completed',
                __('Order shipped by Printify', 'wp-woocommerce-printify-sync')
            );
        }

        $order->save();

        // Send customer notification
        if ($this->config->get('send_shipping_notification', true)) {
            do_action('wpwps_send_shipping_notification', $order);
        }
    }

    private function handleOrderDelivered(array $data): void
    {
        $externalId = $data['external_id'] ?? null;
        if (!$externalId) {
            throw new \Exception('Missing external order ID');
        }

        $order = wc_get_order($externalId);
        if (!$order) {
            throw new \Exception("Order not found: {$externalId}");
        }

        $order->update_meta_data('_printify_delivery_date', current_time('mysql', true));
        
        if ($order->get_status() !== 'completed') {
            $order->update_status(
                'completed',
                __('Order delivered - Printify', 'wp-woocommerce-printify-sync')
            );
        }

        $order->save();
    }

    private function handleOrderCancelled(array $data): void
    {
        $externalId = $data['external_id'] ?? null;
        if (!$externalId) {
            throw new \Exception('Missing external order ID');
        }

        $order = wc_get_order($externalId);
        if (!$order) {
            throw new \Exception("Order not found: {$externalId}");
        }

        if ($order->get_status() !== 'cancelled') {
            $order->update_status(
                'cancelled',
                sprintf(
                    __('Order cancelled by Printify. Reason: %s', 'wp-woocommerce-printify-sync'),
                    $data['reason'] ?? 'No reason provided'
                )
            );
        }

        $order->save();
    }

    private function mapPrintifyStatusToWooCommerce(string $status): ?string
    {
        $statusMap = [
            'pending' => 'processing',
            'in_production' => 'processing',
            'shipped' => 'completed',
            'delivered' => 'completed',
            'cancelled' => 'cancelled',
            'failed' => 'failed'
        ];

        return $statusMap[$status] ?? null;
    }
}