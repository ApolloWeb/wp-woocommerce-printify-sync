<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class OrderLogger {
    private $logger;

    public function __construct(LoggerService $logger) {
        $this->logger = $logger;
    }

    public function logOrderSync(\WC_Order $order, array $printify_response): void {
        $log_entry = [
            'order_id' => $order->get_id(),
            'printify_id' => $printify_response['id'] ?? null,
            'status' => $printify_response['status'] ?? null,
            'shipping_info' => $printify_response['shipping'] ?? [],
            'line_items' => array_map(function($item) {
                return [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity']
                ];
            }, $printify_response['line_items'] ?? [])
        ];

        $this->logger->info('Order synced with Printify', $log_entry);
        
        update_post_meta($order->get_id(), '_printify_sync_log', $log_entry);
    }
}
