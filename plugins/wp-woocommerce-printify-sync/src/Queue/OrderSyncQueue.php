<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Queue;

use ApolloWeb\WPWooCommercePrintifySync\Services\{
    ConfigService,
    LoggerInterface
};

class OrderSyncQueue
{
    private const QUEUE_GROUP = 'wpwps_order_sync';
    private const SINGLE_SYNC_ACTION = 'wpwps_sync_single_order';
    private const BATCH_SYNC_ACTION = 'wpwps_sync_order_batch';

    private ConfigService $config;
    private LoggerInterface $logger;

    public function __construct(ConfigService $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->initHooks();
    }

    private function initHooks(): void
    {
        add_action(self::SINGLE_SYNC_ACTION, [$this, 'processSingleOrder'], 10, 2);
        add_action(self::BATCH_SYNC_ACTION, [$this, 'processBatchOrders'], 10, 1);
        add_action('init', [$this, 'registerCronEvents']);
    }

    public function registerCronEvents(): void
    {
        if (!wp_next_scheduled(self::BATCH_SYNC_ACTION)) {
            wp_schedule_event(
                time(),
                'every_5_minutes',
                self::BATCH_SYNC_ACTION
            );
        }
    }

    public function queueOrder(int $orderId, array $metadata = []): void
    {
        as_enqueue_async_action(
            self::SINGLE_SYNC_ACTION,
            [
                'order_id' => $orderId,
                'metadata' => $metadata,
                'timestamp' => current_time('mysql', true),
                'user' => get_current_user_id()
            ],
            self::QUEUE_GROUP
        );

        $this->logger->info('Order queued for sync', [
            'order_id' => $orderId,
            'queued_at' => current_time('mysql', true)
        ]);
    }

    public function processSingleOrder(int $orderId, array $args): void
    {
        try {
            $order = wc_get_order($orderId);
            if (!$order) {
                throw new \Exception("Order not found: {$orderId}");
            }

            // Update sync status
            $order->update_meta_data('_printify_sync_status', 'processing');
            $order->update_meta_data('_printify_sync_attempt', time());
            $order->save();

            // Process the order
            $this->syncOrder($order, $args['metadata'] ?? []);

            $this->logger->info('Order sync completed', [
                'order_id' => $orderId,
                'sync_time' => current_time('mysql', true)
            ]);

        } catch (\Exception $e) {
            $this->handleSyncError($orderId, $e);
        }
    }

    public function processBatchOrders(): void
    {
        $limit = (int)$this->config->get('batch_size', 50);
        $orders = $this->getPendingOrders($limit);

        foreach ($orders as $orderId) {
            $this->queueOrder($orderId);
        }

        $this->logger->info('Batch order sync initiated', [
            'order_count' => count($orders),
            'batch_time' => current_time('mysql', true)
        ]);
    }

    private function getPendingOrders(int $limit): array
    {
        global $wpdb;

        // Using HPOS compatible query
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            $orderTable = OrderUtil::get_orders_table_name();
            $metaTable = OrderUtil::get_meta_table_name();

            return $wpdb->get_col($wpdb->prepare("
                SELECT o.id
                FROM {$orderTable} o
                LEFT JOIN {$metaTable} m ON o.id = m.order_id 
                    AND m.meta_key = '_printify_sync_status'
                WHERE o.status IN ('processing', 'pending')
                AND (m.meta_value IS NULL OR m.meta_value IN ('pending', 'failed'))
                LIMIT %d
            ", $limit));
        }

        // Legacy post meta query
        return $wpdb->get_col($wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                AND pm.meta_key = '_printify_sync_status'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-processing', 'wc-pending')
            AND (pm.meta_value IS NULL OR pm.meta_value IN ('pending', 'failed'))
            LIMIT %d
        ", $limit));
    }

    private function syncOrder(\WC_Order $order, array $metadata = []): void
    {
        // Get order handler service from container
        $orderHandler = container()->get(OrderHandler::class);
        
        // Sync the order
        $orderHandler->handleNewOrder(
            $order->get_id(),
            $metadata,
            $order
        );

        // Update sync status
        $order->update_meta_data('_printify_sync_status', 'completed');
        $order->update_meta_data('_printify_sync_completed', current_time('mysql', true));
        $order->save();
    }

    private function handleSyncError(int $orderId, \Exception $error): void
    {
        $order = wc_get_order($orderId);
        if ($order) {
            $order->update_meta_data('_printify_sync_status', 'failed');
            $order->update_meta_data('_printify_sync_error', $error->getMessage());
            $order->update_meta_data('_printify_sync_error_time', current_time('mysql', true));
            $order->save();
        }

        $this->logger->error('Order sync failed', [
            'order_id' => $orderId,
            'error' => $error->getMessage(),
            'time' => current_time('mysql', true)
        ]);

        // Retry logic
        $retryCount = (int)get_post_meta($orderId, '_printify_sync_retry_count', true);
        if ($retryCount < 3) {
            as_schedule_single_action(
                time() + (15 * 60), // Retry after 15 minutes
                self::SINGLE_SYNC_ACTION,
                [
                    'order_id' => $orderId,
                    'metadata' => ['retry_count' => $retryCount + 1]
                ],
                self::QUEUE_GROUP
            );
        }
    }
}