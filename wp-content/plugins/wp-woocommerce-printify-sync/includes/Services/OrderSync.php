<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Automattic\WooCommerce\Utilities\OrderUtil;

class OrderSync {
    private $api;
    private $logger;
    private $job_manager;
    private $status_map = [
        'pending' => 'on-hold',
        'in_production' => 'processing',
        'shipping' => 'completed',
        'canceled' => 'cancelled',
        'refunded' => 'refunded'
    ];

    public function __construct(PrintifyApi $api, Logger $logger, JobManager $job_manager) {
        $this->api = $api;
        $this->logger = $logger;
        $this->job_manager = $job_manager;

        add_action('wpps_sync_order', [$this, 'syncOrder']);
        add_action('woocommerce_order_status_changed', [$this, 'handleStatusChange'], 10, 3);
        add_action('wpps_process_webhook_order', [$this, 'handleWebhook']);
    }

    public function syncOrder($order_id): void {
        try {
            $order = wc_get_order($order_id);
            $printify_id = $this->getOrderMeta($order, '_printify_order_id');
            
            if (!$printify_id) {
                $this->syncOrderToPrintify($order);
                return;
            }

            $this->updatePrintifyOrder($order, $printify_id);
        } catch (\Exception $e) {
            $this->logger->log("Order sync failed: " . $e->getMessage(), 'error');
        }
    }

    public function handleWebhook(array $data): void {
        if (!isset($data['order_id'])) {
            return;
        }

        $order = wc_get_order($data['external_id'] ?? '');
        if (!$order) {
            return;
        }

        // Update order status
        $new_status = $this->mapStatus($data['status']);
        $order->update_status($new_status);

        // Update tracking info if available
        if (isset($data['tracking'])) {
            $this->updateTracking($order, $data['tracking']);
        }

        $order->save();
    }

    private function updateTracking($order, array $tracking): void {
        $this->updateOrderMeta($order, '_printify_tracking_number', $tracking['number']);
        $this->updateOrderMeta($order, '_printify_tracking_url', $tracking['url']);
        $this->updateOrderMeta($order, '_printify_carrier', $tracking['carrier']);
        
        // Add order note
        $order->add_order_note(
            sprintf(
                __('Tracking updated - Carrier: %s, Number: %s', 'wp-woocommerce-printify-sync'),
                $tracking['carrier'],
                $tracking['number']
            )
        );
    }

    private function mapStatus(string $printify_status): string {
        return $this->status_map[$printify_status] ?? 'pending';
    }

    private function updateOrderMeta($order, string $key, $value): void {
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            $order->update_meta_data($key, $value);
            $order->save();
        } else {
            update_post_meta($order->get_id(), $key, $value);
        }
    }

    private function getOrderMeta($order, string $key) {
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            return $order->get_meta($key);
        }
        return get_post_meta($order->get_id(), $key, true);
    }

    private function syncOrderToPrintify($order): void {
        $lock_id = 'order_' . $order->get_id();
        
        if ($this->job_manager->isLocked($lock_id)) {
            throw new \Exception("Order sync already in progress");
        }

        try {
            $this->job_manager->lockJob($lock_id, 'order_sync');
            
            $this->validateOrder($order);
            $line_items = $this->prepareLineItems($order);
            
            if (empty($line_items)) {
                throw new \Exception("No valid printify products in order");
            }

            $printify_order = [
                'external_id' => $order->get_id(),
                'line_items' => $line_items,
                'shipping_address' => $this->formatShippingAddress($order),
                'shipping_method' => $this->getShippingMethod($order),
                'send_shipping_notification' => true,
                'metadata' => [
                    'wc_order_id' => $order->get_id(),
                    'wc_order_number' => $order->get_order_number()
                ]
            ];

            $response = $this->retryWithBackoff(function() use ($printify_order) {
                return $this->api->createOrder($printify_order);
            });

            $this->updateOrderMeta($order, [
                'printify_order_id' => $response['id'],
                'sync_status' => 'success',
                'last_synced' => current_time('mysql')
            ]);

        } catch (\Exception $e) {
            $this->updateOrderMeta($order, [
                'sync_status' => 'failed',
                'sync_error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            $this->job_manager->unlockJob($lock_id);
        }
    }

    private function validateOrder($order): void {
        if (!$order->is_paid()) {
            throw new \Exception("Order must be paid before syncing to Printify");
        }

        if ($this->getOrderMeta($order, 'printify_order_id')) {
            throw new \Exception("Order already synced to Printify");
        }
    }

    private function retryWithBackoff(callable $callback, int $max_attempts = 3): mixed {
        $attempt = 1;
        $delay = 1;

        while ($attempt <= $max_attempts) {
            try {
                return $callback();
            } catch (\Exception $e) {
                if ($attempt === $max_attempts) {
                    throw $e;
                }
                sleep($delay);
                $delay *= 2;
                $attempt++;
            }
        }
    }

    private function formatShippingAddress($order): array {
        return [
            'first_name' => $order->get_shipping_first_name(),
            'last_name' => $order->get_shipping_last_name(),
            'address1' => $order->get_shipping_address_1(),
            'address2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'state' => $order->get_shipping_state(),
            'country' => $order->get_shipping_country(),
            'zip' => $order->get_shipping_postcode(),
            'phone' => $order->get_billing_phone(),
            'email' => $order->get_billing_email()
        ];
    }

    private function validateWebhookSignature(string $payload, string $signature): bool {
        return hash_equals(
            hash_hmac('sha256', $payload, $this->settings->get('webhook_secret')),
            $signature
        );
    }
}
