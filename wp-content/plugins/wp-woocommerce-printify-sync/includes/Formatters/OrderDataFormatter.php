<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Formatters;

use ApolloWeb\WPWooCommercePrintifySync\Repositories\ProductRepository;

/**
 * Order Data Formatter
 * 
 * Formats order data for API requests and responses
 */
class OrderDataFormatter {
    /**
     * @var ProductRepository
     */
    private $product_repository;
    
    /**
     * Constructor
     */
    public function __construct(ProductRepository $product_repository = null) {
        $this->product_repository = $product_repository ?? new ProductRepository();
    }
    
    /**
     * Format WooCommerce order data for Printify API
     *
     * @param \WC_Order $order WooCommerce order
     * @return array Printify order data
     * @throws \Exception If order contains no Printify products
     */
    public function formatForPrintify(\WC_Order $order): array {
        // Line items
        $line_items = $this->formatLineItems($order);
        
        if (empty($line_items)) {
            throw new \Exception('Order contains no Printify products');
        }
        
        // Shipping address
        $address = $this->formatShippingAddress($order);
        
        // Basic order data
        $data = [
            'external_id' => (string) $order->get_id(),
            'label' => sprintf('WC #%s', $order->get_order_number()),
            'line_items' => $line_items,
            'shipping_address' => $address,
            'shipping_method' => $this->getShippingMethod($order),
            'send_shipping_notification' => true
        ];
        
        return $data;
    }
    
    /**
     * Format line items for Printify API
     *
     * @param \WC_Order $order WooCommerce order
     * @return array Formatted line items
     */
    private function formatLineItems(\WC_Order $order): array {
        $line_items = [];
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            
            if (!$product) {
                continue;
            }
            
            // Get Printify product ID
            $printify_product_id = get_post_meta($product->get_id(), '_printify_product_id', true);
            
            if (!$printify_product_id) {
                // Skip products that aren't from Printify
                continue;
            }
            
            // Get Printify variant ID for variations
            $printify_variant_id = '';
            if ($product->is_type('variation')) {
                $printify_variant_id = get_post_meta($product->get_id(), '_printify_variant_id', true);
            }
            
            $line_item = [
                'product_id' => $printify_product_id,
                'quantity' => $item->get_quantity()
            ];
            
            // Add variant ID if available
            if ($printify_variant_id) {
                $line_item['variant_id'] = $printify_variant_id;
            }
            
            $line_items[] = $line_item;
        }
        
        return $line_items;
    }
    
    /**
     * Format shipping address for Printify API
     *
     * @param \WC_Order $order WooCommerce order
     * @return array Formatted address
     */
    private function formatShippingAddress(\WC_Order $order): array {
        return [
            'first_name' => $order->get_shipping_first_name() ?: $order->get_billing_first_name(),
            'last_name' => $order->get_shipping_last_name() ?: $order->get_billing_last_name(),
            'address1' => $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
            'address2' => $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
            'city' => $order->get_shipping_city() ?: $order->get_billing_city(),
            'state' => $order->get_shipping_state() ?: $order->get_billing_state(),
            'zip' => $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
            'country' => $order->get_shipping_country() ?: $order->get_billing_country(),
            'phone' => $order->get_billing_phone(),
            'email' => $order->get_billing_email()
        ];
    }
    
    /**
     * Get shipping method for Printify API
     *
     * @param \WC_Order $order WooCommerce order
     * @return string Shipping method
     */
    private function getShippingMethod(\WC_Order $order): string {
        // Default to standard shipping
        return '1';
    }
    
    /**
     * Map Printify order status to WooCommerce status
     *
     * @param string $printify_status Printify order status
     * @return string WooCommerce order status
     */
    public function getPrintifyToWooStatus(string $printify_status): string {
        $status_map = [
            // Pre-production statuses
            'on_hold' => 'on-hold',
            'awaiting_customer_evidence' => 'on-hold',
            'submit_order' => 'pending',
            'action_required' => 'pending',
            
            // Production statuses
            'in_production' => 'processing',
            'has_issues' => 'processing',
            'canceled_by_provider' => 'cancelled',
            'canceled' => 'cancelled',
            
            // Shipping statuses
            'ready_to_ship' => 'processing',
            'shipped' => 'completed',
            'on_the_way' => 'completed',
            'available_for_pickup' => 'completed',
            'out_for_delivery' => 'completed',
            'delivery_attempt' => 'completed',
            'shipping_issue' => 'failed',
            'return_to_sender' => 'failed',
            'delivered' => 'completed',
            
            // Refund and reprint statuses
            'refund_awaiting_customer_evidence' => 'on-hold',
            'refund_requested' => 'on-hold',
            'refund_approved' => 'refunded',
            'refund_declined' => 'completed',
            'reprint_awaiting_customer_evidence' => 'on-hold',
            'reprint_requested' => 'on-hold',
            'reprint_approved' => 'processing',
            'reprint_declined' => 'completed'
        ];
        
        // Normalize status to lowercase and replace spaces with underscores
        $normalized_status = strtolower(str_replace(' ', '_', $printify_status));
        
        return isset($status_map[$normalized_status]) ? $status_map[$normalized_status] : 'processing';
    }
}
