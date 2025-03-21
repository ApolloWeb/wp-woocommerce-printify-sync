<?php
/**
 * Order Sync.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Orders
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Order Sync class.
 */
class OrderSync {
    /**
     * PrintifyAPI instance.
     *
     * @var PrintifyAPI
     */
    private $api;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * OrderStatuses instance.
     *
     * @var OrderStatuses
     */
    private $order_statuses;

    /**
     * Constructor.
     *
     * @param PrintifyAPI   $api           PrintifyAPI instance.
     * @param Logger        $logger        Logger instance.
     * @param OrderStatuses $order_statuses OrderStatuses instance.
     */
    public function __construct(
        PrintifyAPI $api,
        Logger $logger,
        OrderStatuses $order_statuses
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->order_statuses = $order_statuses;
    }

    /**
     * Initialize order sync.
     *
     * @return void
     */
    public function init() {
        // Register Action Scheduler handlers.
        add_action('wpwps_sync_order', [$this, 'syncOrder'], 10, 1);
    }

    /**
     * Sync order with Printify.
     *
     * @param int $order_id WooCommerce order ID.
     * @return void
     */
    public function syncOrder($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            $this->logger->error(
                sprintf('Failed to sync order %s', $order_id),
                ['error' => 'Order not found']
            );
            return;
        }
        
        // Prepare order data for Printify.
        $order_data = $this->prepareOrderData($order);
        
        if (empty($order_data['line_items'])) {
            $this->logger->info(
                sprintf('Order %s has no Printify products', $order_id),
                ['order_id' => $order_id]
            );
            return;
        }
        
        // Send order to Printify.
        $response = $this->api->createOrder($order_data);
        
        if (is_wp_error($response)) {
            $this->logger->error(
                sprintf('Failed to create order %s on Printify', $order_id),
                ['error' => $response->get_error_message(), 'order_id' => $order_id]
            );
            return;
        }
        
        $this->logger->info(
            sprintf('Order %s created on Printify', $order_id),
            ['order_id' => $order_id, 'printify_order_id' => $response['id']]
        );
        
        // Store Printify order ID.
        update_post_meta($order_id, '_printify_order_id', $response['id']);
    }

    /**
     * Prepare order data for Printify API.
     *
     * @param \WC_Order $order WooCommerce order.
     * @return array
     */
    private function prepareOrderData($order) {
        $settings = get_option('wpwps_settings', []);
        $use_external_id = isset($settings['sync_external_order_id']) && $settings['sync_external_order_id'];
        
        $order_data = [
            'label' => sprintf('#%s - %s %s', $order->get_order_number(), $order->get_billing_first_name(), $order->get_billing_last_name()),
        ];
        
        // Add external_id if setting enabled
        if ($use_external_id) {
            $order_data['external_id'] = (string) $order->get_id();
        }
        
        // Add line items.
        $line_items = [];
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            
            // Get Printify product ID.
            $printify_product_id = get_post_meta($product_id, '_printify_product_id', true);
            
            if (!$printify_product_id) {
                continue;
            }
            
            // Get Printify variant ID.
            $product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
            
            if (!$product) {
                continue;
            }
            
            $printify_variant_id = $product->get_meta('_printify_variant_id');
            
            if (!$printify_variant_id) {
                continue;
            }
            
            $line_items[] = [
                'product_id' => $printify_product_id,
                'variant_id' => $printify_variant_id,
                'quantity' => $item->get_quantity(),
            ];
        }
        
        $order_data['line_items'] = $line_items;
        
        // Add shipping address.
        $order_data['shipping_address'] = [
            'first_name' => $order->get_shipping_first_name() ?: $order->get_billing_first_name(),
            'last_name' => $order->get_shipping_last_name() ?: $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'address1' => $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
            'address2' => $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
            'city' => $order->get_shipping_city() ?: $order->get_billing_city(),
            'state' => $order->get_shipping_state() ?: $order->get_billing_state(),
            'country' => $order->get_shipping_country() ?: $order->get_billing_country(),
            'zip' => $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
        ];
        
        return $order_data;
    }

    /**
     * Update order status based on Printify status.
     *
     * @param int    $order_id WooCommerce order ID.
     * @param string $printify_status Printify order status.
     * @return bool
     */
    public function updateOrderStatus($order_id, $printify_status) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            $this->logger->error(
                sprintf('Failed to update order status for %s', $order_id),
                ['error' => 'Order not found']
            );
            return false;
        }
        
        // Map Printify status to WooCommerce status
        $wc_status = $this->order_statuses->mapPrintifyStatusToWooCommerce($printify_status);
        
        // Update order status if it's different
        if ('wc-' . $order->get_status() !== $wc_status) {
            $order->update_status(
                str_replace('wc-', '', $wc_status),
                sprintf(__('Status updated from Printify: %s', 'wp-woocommerce-printify-sync'), $printify_status)
            );
            
            $this->logger->info(
                sprintf('Order %s status updated to %s', $order_id, $wc_status),
                ['order_id' => $order_id, 'printify_status' => $printify_status]
            );
        }
        
        return true;
    }
}
