<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class OrderService {
    private $api_service;

    private $status_mappings = [
        // Pre-production statuses
        'pending' => 'on-hold',
        'awaiting_customer_evidence' => 'on-hold',
        'submit_order' => 'processing',
        'action_required' => 'on-hold',
        
        // Production statuses
        'in_production' => 'processing',
        'has_issues' => 'on-hold',
        'canceled_by_provider' => 'cancelled',
        'canceled' => 'cancelled',
        
        // Shipping statuses
        'ready_to_ship' => 'processing',
        'shipped' => 'completed',
        'on_the_way' => 'completed',
        'available_for_pickup' => 'completed',
        'out_for_delivery' => 'completed',
        'delivery_attempt' => 'completed',
        'shipping_issue' => 'on-hold',
        'return_to_sender' => 'failed',
        'delivered' => 'completed',
        
        // Refund statuses
        'refund_awaiting_customer_evidence' => 'on-hold',
        'refund_requested' => 'on-hold',
        'refund_approved' => 'refunded',
        'refund_declined' => 'processing',
        
        // Reprint statuses
        'reprint_awaiting_customer_evidence' => 'on-hold',
        'reprint_requested' => 'on-hold',
        'reprint_approved' => 'processing',
        'reprint_declined' => 'processing'
    ];

    public function __construct() {
        $this->api_service = new ApiService();
        
        add_action('wp_ajax_wpwps_sync_orders', [$this, 'ajaxSyncOrders']);
        add_action('wpwps_scheduled_order_sync', [$this, 'syncOrders']);
        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChange'], 10, 4);
        add_action('woocommerce_new_order', [$this, 'handleNewOrder']);
    }

    public function ajaxSyncOrders(): void {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-woocommerce-printify-sync'));
        }

        as_schedule_single_action(time(), 'wpwps_scheduled_order_sync');
        
        wp_send_json_success([
            'message' => __('Order sync scheduled', 'wp-woocommerce-printify-sync')
        ]);
    }

    public function syncOrders(): void {
        $response = $this->api_service->getOrders();
        
        if (!$response['success']) {
            do_action('wpwps_log_error', 'Order sync failed', $response);
            return;
        }

        foreach ($response['data'] as $printify_order) {
            $this->syncOrder($printify_order);
        }

        do_action('wpwps_log_info', 'Order sync completed', [
            'total_orders' => count($response['data'])
        ]);
    }

    private function syncOrder(array $printify_order): void {
        $order_id = $this->getOrderIdByPrintifyId($printify_order['id']);
        
        if ($order_id) {
            $this->updateOrder($order_id, $printify_order);
        } else {
            $this->createOrder($printify_order);
        }
    }

    public function handleNewOrder(int $order_id): void {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }

        $printify_data = $this->preparePrintifyOrderData($order);
        $response = $this->api_service->createOrder($printify_data);
        
        if ($response['success']) {
            update_post_meta($order_id, '_printify_order_id', $response['data']['id']);
            $order->add_order_note(__('Order created in Printify', 'wp-woocommerce-printify-sync'));
        } else {
            $order->add_order_note(__('Failed to create order in Printify', 'wp-woocommerce-printify-sync'));
            do_action('wpwps_log_error', 'Failed to create Printify order', [
                'order_id' => $order_id,
                'response' => $response
            ]);
        }
    }

    public function handleOrderStatusChange(int $order_id, string $old_status, string $new_status, \WC_Order $order): void {
        $printify_id = get_post_meta($order_id, '_printify_order_id', true);
        
        if (!$printify_id) {
            return;
        }

        // Map WooCommerce status to Printify status
        $printify_status = array_search($new_status, $this->status_mappings);
        
        if ($printify_status) {
            $response = $this->api_service->updateOrderStatus($printify_id, [
                'status' => $printify_status
            ]);
            
            if (!$response['success']) {
                $order->add_order_note(__('Failed to update order status in Printify', 'wp-woocommerce-printify-sync'));
                do_action('wpwps_log_error', 'Failed to update Printify order status', [
                    'order_id' => $order_id,
                    'printify_id' => $printify_id,
                    'response' => $response
                ]);
            }
        }
    }

    private function createOrder(array $printify_data): int {
        $order = wc_create_order();
        
        $this->updateOrderData($order, $printify_data);
        
        update_post_meta($order->get_id(), '_printify_order_id', $printify_data['id']);
        update_post_meta($order->get_id(), '_printify_last_synced', current_time('mysql'));
        
        return $order->get_id();
    }

    private function updateOrder(int $order_id, array $printify_data): void {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }

        $this->updateOrderData($order, $printify_data);
        update_post_meta($order_id, '_printify_last_synced', current_time('mysql'));
    }

    private function updateOrderData(\WC_Order $order, array $printify_data): void {
        // Update status
        if (isset($this->status_mappings[$printify_data['status']])) {
            $order->set_status($this->status_mappings[$printify_data['status']]);
        }

        // Update line items
        $this->updateOrderItems($order, $printify_data['line_items']);
        
        // Update shipping
        if (!empty($printify_data['shipping'])) {
            $this->updateOrderShipping($order, $printify_data['shipping']);
        }
        
        // Update tracking info
        if (!empty($printify_data['tracking'])) {
            $this->updateOrderTracking($order, $printify_data['tracking']);
        }

        $order->calculate_totals();
        $order->save();
    }

    private function updateOrderItems(\WC_Order $order, array $items): void {
        // Remove existing items
        foreach ($order->get_items() as $item) {
            $order->remove_item($item->get_id());
        }

        // Add new items
        foreach ($items as $item) {
            $product_id = $this->getProductIdByPrintifyId($item['product_id']);
            $variation_id = $this->getVariationIdByPrintifyId($product_id, $item['variant_id']);
            
            if ($product_id && $variation_id) {
                $order->add_product(wc_get_product($variation_id), $item['quantity'], [
                    'subtotal' => $item['price'],
                    'total' => $item['price']
                ]);
            }
        }
    }

    private function updateOrderShipping(\WC_Order $order, array $shipping): void {
        foreach ($order->get_shipping_methods() as $method) {
            $order->remove_item($method->get_id());
        }

        $order->add_shipping([
            'method_title' => $shipping['method'],
            'method_id' => 'printify_shipping',
            'total' => $shipping['cost']
        ]);
    }

    private function updateOrderTracking(\WC_Order $order, array $tracking): void {
        update_post_meta($order->get_id(), '_printify_tracking_number', $tracking['number']);
        update_post_meta($order->get_id(), '_printify_tracking_url', $tracking['url']);
        update_post_meta($order->get_id(), '_printify_carrier', $tracking['carrier']);
        
        $order->add_order_note(
            sprintf(
                __('Tracking updated - Carrier: %s, Number: %s', 'wp-woocommerce-printify-sync'),
                $tracking['carrier'],
                $tracking['number']
            )
        );
    }

    private function preparePrintifyOrderData(\WC_Order $order): array {
        $data = [
            'external_id' => $order->get_id(),
            'line_items' => [],
            'shipping_address' => [
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
            ]
        ];

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $printify_product_id = get_post_meta($product->get_parent_id(), '_printify_product_id', true);
            $printify_variant_id = get_post_meta($product->get_id(), '_printify_variant_id', true);
            
            if ($printify_product_id && $printify_variant_id) {
                $data['line_items'][] = [
                    'product_id' => $printify_product_id,
                    'variant_id' => $printify_variant_id,
                    'quantity' => $item->get_quantity()
                ];
            }
        }

        return $data;
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

    private function getVariationIdByPrintifyId(int $product_id, string $variant_id): ?int {
        global $wpdb;
        
        $variation_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_variant_id' 
            AND meta_value = %s 
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_parent = %d 
                AND post_type = 'product_variation'
            )
            LIMIT 1",
            $variant_id,
            $product_id
        ));
        
        return $variation_id ? (int) $variation_id : null;
    }
}