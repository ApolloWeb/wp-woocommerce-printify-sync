<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class OrderSync {
    private $api;
    private $logger;

    public function __construct(PrintifyApi $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
    }

    public function syncOrders(): void {
        try {
            $orders = wc_get_orders([
                'status' => ['processing'],
                'meta_key' => '_printify_sync_status',
                'meta_compare' => 'NOT EXISTS'
            ]);

            foreach ($orders as $order) {
                $this->syncOrder($order);
            }
        } catch (\Exception $e) {
            $this->logger->log($e->getMessage(), 'error');
        }
    }

    private function syncOrder($order): void {
        // Order sync implementation
    }
}
