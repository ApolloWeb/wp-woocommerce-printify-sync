<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Support;

use WC_Order;
use WC_Order_Refund;

class RefundManager {
    private $table_name;

    public function __construct() 
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpwps_refunds';
    }

    public function processRefund(int $order_id, float $amount, string $reason = ''): bool 
    {
        try {
            $order = wc_get_order($order_id);
            if (!$order instanceof WC_Order) {
                throw new \Exception('Invalid order ID');
            }

            // Create WooCommerce refund
            $refund = wc_create_refund([
                'amount' => $amount,
                'reason' => $reason,
                'order_id' => $order_id,
                'line_items' => [],
            ]);

            if (is_wp_error($refund)) {
                throw new \Exception($refund->get_error_message());
            }

            // Sync with Printify
            $this->syncPrintifyRefund($order, $refund);

            // Log refund
            $this->logRefund($order_id, $amount, $reason);

            return true;
        } catch (\Exception $e) {
            error_log('Refund processing failed: ' . $e->getMessage());
            return false;
        }
    }

    private function syncPrintifyRefund(WC_Order $order, WC_Order_Refund $refund): void 
    {
        // Implement Printify API refund sync
        // To be implemented based on Printify's API
    }

    private function logRefund(int $order_id, float $amount, string $reason): void 
    {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            [
                'order_id' => $order_id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'completed',
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%f', '%s', '%s', '%s']
        );
    }

    public function getRefunds(array $args = []): array 
    {
        global $wpdb;
        
        $defaults = [
            'per_page' => 10,
            'page' => 1,
        ];

        $args = wp_parse_args($args, $defaults);
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $args['per_page'],
                $offset
            ),
            ARRAY_A
        );
    }
}
