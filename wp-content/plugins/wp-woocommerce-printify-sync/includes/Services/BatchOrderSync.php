<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class BatchOrderSync {
    private $api;
    private $logger;
    private $batch_size = 20;

    public function __construct(PrintifyApi $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;

        add_action('wpps_process_order_batch', [$this, 'processBatch']);
    }

    public function scheduleBatchSync(): void {
        $args = ['batch_id' => uniqid('order_batch_')];
        as_enqueue_async_action('wpps_process_order_batch', [$args]);
    }

    public function processBatch(array $args): void {
        $orders = $this->getUnprocessedOrders();
        
        foreach ($orders as $order) {
            $this->syncOrder($order);
            as_schedule_single_action(
                time() + 2,
                'wpps_sync_order_status',
                ['order_id' => $order->get_id()]
            );
        }
    }

    private function getUnprocessedOrders(): array {
        return wc_get_orders([
            'status' => ['processing'],
            'limit' => $this->batch_size,
            'meta_query' => [
                [
                    'key' => '_printify_sync_status',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
    }

    private function syncOrder($order): void {
        try {
            // Sync logic implementation
            $order->update_meta_data('_printify_sync_status', 'processing');
            $order->save();
        } catch (\Exception $e) {
            $this->logger->log("Order sync failed: " . $e->getMessage(), 'error');
        }
    }
}
