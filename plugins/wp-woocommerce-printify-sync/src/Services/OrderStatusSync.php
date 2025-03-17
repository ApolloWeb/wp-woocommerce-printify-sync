<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Automattic\WooCommerce\Utilities\OrderUtil;

class OrderStatusSync
{
    private const SYNC_BATCH_SIZE = 50;
    private ConfigService $config;
    private PrintifyAPI $printifyApi;
    private LoggerInterface $logger;

    public function __construct(
        ConfigService $config,
        PrintifyAPI $printifyApi,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->printifyApi = $printifyApi;
        $this->logger = $logger;

        $this->initHooks();
    }

    private function initHooks(): void
    {
        add_action('wpwps_sync_order_statuses', [$this, 'syncOrderStatuses']);
        add_action('init', [$this, 'scheduleSyncCron']);
    }

    public function scheduleSyncCron(): void
    {
        if (!wp_next_scheduled('wpwps_sync_order_statuses')) {
            wp_schedule_event(
                time(),
                'every_15_minutes',
                'wpwps_sync_order_statuses'
            );
        }
    }

    public function syncOrderStatuses(): void
    {
        try {
            $orders = $this->getPendingStatusOrders();
            
            foreach ($orders as $orderId) {
                try {
                    $this->syncOrderStatus($orderId);
                } catch (\Exception $e) {
                    $this->logger->error('Failed to sync order status', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logger->info('Order status sync completed', [
                'orders_processed' => count($orders),
                'timestamp' => current_time('mysql', true)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Order status sync failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getPendingStatusOrders(): array
    {
        global $wpdb;

        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            $orderTable = OrderUtil::get_orders_table_name();
            $metaTable = OrderUtil::get_meta_table_name();

            return $wpdb->get_col($wpdb->prepare("
                SELECT o.id
                FROM {$orderTable} o
                INNER JOIN {$metaTable} m ON o.id = m.order_id
                WHERE m.meta_key = '_printify_order_id'
                AND o.status IN ('pending', 'processing')
                LIMIT %d
            ", self::SYNC_BATCH_SIZE));
        }

        return $wpdb->get_col($wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-pending', 'wc-processing')
            AND pm.meta_key = '_printify_order_id'
            LIMIT %d
        ", self::SYNC_BATCH_SIZE));
    }

    private function syncOrderStatus(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (!$order) {
            throw new \Exception("Order not found: {$orderId}");
        }

        $printifyOrderId = $order->get_meta('_printify_order_id');
        if (!$printifyOrderId) {
            return;
        }

        $printifyOrder = $this->printifyApi->getOrder($printifyOrderId);
        
        // Update order status if changed
        $wcStatus = $this->mapPrintifyStatusToWooCommerce($printifyOrder['status']);
        if ($wcStatus && $wcStatus !== $order->get_status()) {
            $order->update_status(
                $wcStatus,
                __('Status synchronized from Printify', 'wp-woocommerce-printify-sync')
            );
        }

        // Update order metadata
        $order->update_meta_data('_printify_status', $printifyOrder['status']);
        $order->update_meta_data('_printify_status_updated', current_time('mysql', true));
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