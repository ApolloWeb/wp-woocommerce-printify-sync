<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Core\HPOSCompat;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Repositories\OrderRepository;
use ApolloWeb\WPWooCommercePrintifySync\Repositories\ProductRepository;
use ApolloWeb\WPWooCommercePrintifySync\Formatters\OrderDataFormatter;

/**
 * Order Sync Service
 * 
 * Handles syncing orders between WooCommerce and Printify
 */
class OrderSyncService {
    /**
     * @var PrintifyApiClient
     */
    private $api;
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var OrderRepository
     */
    private $order_repository;
    
    /**
     * @var ProductRepository
     */
    private $product_repository;
    
    /**
     * @var OrderDataFormatter
     */
    private $data_formatter;
    
    /**
     * Constructor
     */
    public function __construct(
        PrintifyApiClient $api, 
        Logger $logger,
        OrderRepository $order_repository = null,
        ProductRepository $product_repository = null,
        OrderDataFormatter $data_formatter = null
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->order_repository = $order_repository ?? new OrderRepository();
        $this->product_repository = $product_repository ?? new ProductRepository();
        $this->data_formatter = $data_formatter ?? new OrderDataFormatter();
    }
    
    /**
     * Import an order from Printify to WooCommerce
     *
     * @param string $printify_order_id Printify order ID
     * @return int|false WooCommerce order ID or false on failure
     */
    public function importOrder(string $printify_order_id) {
        $this->logger->log("Importing Printify order {$printify_order_id}", 'info');
        
        try {
            // Check if order already exists
            $existing_order_id = $this->getWooOrderByPrintifyId($printify_order_id);
            
            if ($existing_order_id) {
                $this->logger->log("Order already exists in WooCommerce with ID #{$existing_order_id}", 'info');
                return $this->updateWooOrder($existing_order_id, $printify_order_id);
            }
            
            // Get order data from Printify
            $printify_order = $this->api->getOrder($printify_order_id);
            
            if (empty($printify_order)) {
                throw new \Exception("No data returned from Printify for order {$printify_order_id}");
            }
            
            // Create WooCommerce order
            $order_id = $this->createWooOrder($printify_order);
            
            $this->logger->log("Successfully imported Printify order {$printify_order_id} as WooCommerce order #{$order_id}", 'info');
            
            return $order_id;
        } catch (\Exception $e) {
            $this->logger->log("Error importing Printify order {$printify_order_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Update a WooCommerce order with data from Printify
     *
     * @param int $order_id WooCommerce order ID
     * @param string $printify_order_id Printify order ID
     * @return int|false WooCommerce order ID or false on failure
     */
    public function updateWooOrder(int $order_id, string $printify_order_id) {
        $this->logger->log("Updating WooCommerce order #{$order_id} with Printify data", 'info');
        
        try {
            // Get order from Printify
            $printify_order = $this->api->getOrder($printify_order_id);
            
            if (empty($printify_order)) {
                throw new \Exception("No data returned from Printify for order {$printify_order_id}");
            }
            
            // Get WooCommerce order
            $order = wc_get_order($order_id);
            
            if (!$order) {
                throw new \Exception("WooCommerce order #{$order_id} not found");
            }
            
            // Update order status
            $this->updateOrderStatus($order, $printify_order['status']);
            
            // Update tracking information if available
            if (!empty($printify_order['shipments'])) {
                $this->updateTrackingInfo($order, $printify_order['shipments']);
            }
            
            // Update estimated delivery date if available
            if (!empty($printify_order['estimated_delivery_date'])) {
                HPOSCompat::updateOrderMeta($order, '_printify_estimated_delivery', $printify_order['estimated_delivery_date']);
            }
            
            // Add order note
            $order->add_order_note(
                sprintf(__('Order updated from Printify (ID: %s)', 'wp-woocommerce-printify-sync'), $printify_order_id)
            );
            
            $order->save();
            
            $this->logger->log("Successfully updated WooCommerce order #{$order_id}", 'info');
            
            return $order_id;
        } catch (\Exception $e) {
            $this->logger->log("Error updating order #{$order_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Create a new WooCommerce order from Printify data
     *
     * @param array $printify_order Printify order data
     * @return int WooCommerce order ID
     */
    private function createWooOrder(array $printify_order): int {
        // Create order object
        $order = wc_create_order([
            'status' => $this->getWooStatusFromPrintify($printify_order['status'])
        ]);
        
        // Set address
        $address = $printify_order['address'] ?? [];
        
        if (!empty($address)) {
            $order->set_address([
                'first_name' => $address['first_name'] ?? '',
                'last_name' => $address['last_name'] ?? '',
                'company' => $address['company'] ?? '',
                'address_1' => $address['address1'] ?? '',
                'address_2' => $address['address2'] ?? '',
                'city' => $address['city'] ?? '',
                'state' => $address['state'] ?? '',
                'postcode' => $address['zip'] ?? '',
                'country' => $address['country'] ?? '',
                'email' => $address['email'] ?? '',
                'phone' => $address['phone'] ?? ''
            ], 'billing');
            
            // Set shipping same as billing for now
            $order->set_address([
                'first_name' => $address['first_name'] ?? '',
                'last_name' => $address['last_name'] ?? '',
                'company' => $address['company'] ?? '',
                'address_1' => $address['address1'] ?? '',
                'address_2' => $address['address2'] ?? '',
                'city' => $address['city'] ?? '',
                'state' => $address['state'] ?? '',
                'postcode' => $address['zip'] ?? '',
                'country' => $address['country'] ?? '',
            ], 'shipping');
        }
        
        // Add line items
        if (!empty($printify_order['line_items'])) {
            $this->addLineItems($order, $printify_order['line_items']);
        }
        
        // Add shipping
        if (!empty($printify_order['shipping_cost'])) {
            $shipping_item = new \WC_Order_Item_Shipping();
            $shipping_item->set_method_title($printify_order['shipping_method'] ?? __('Standard Shipping', 'wp-woocommerce-printify-sync'));
            $shipping_item->set_total($printify_order['shipping_cost'] / 100); // Convert to dollars
            $order->add_item($shipping_item);
        }
        
        // Add tax
        if (!empty($printify_order['taxes'])) {
            $tax_item = new \WC_Order_Item_Tax();
            $tax_item->set_label(__('Tax', 'wp-woocommerce-printify-sync'));
            $tax_item->set_tax_total($printify_order['taxes'] / 100); // Convert to dollars
            $order->add_item($tax_item);
        }
        
        // Calculate totals
        $order->calculate_totals();
        
        // Set Printify metadata
        HPOSCompat::updateOrderMeta($order, '_printify_order_id', $printify_order['id']);
        HPOSCompat::updateOrderMeta($order, '_printify_linked', '1');
        HPOSCompat::updateOrderMeta($order, '_printify_sync_status', 'synced');
        HPOSCompat::updateOrderMeta($order, '_printify_exchange_rate', 1); // Default 1:1 unless specified
        
        // Set estimated delivery date
        if (!empty($printify_order['estimated_delivery_date'])) {
            HPOSCompat::updateOrderMeta($order, '_printify_estimated_delivery', $printify_order['estimated_delivery_date']);
        } else {
            // Calculate estimate based on production time + shipping
            $production_days = 3; // Default to 3 days
            $shipping_days = 7; // Default to 7 days
            
            $estimated_delivery = strtotime("+{$production_days} days +{$shipping_days} days");
            HPOSCompat::updateOrderMeta($order, '_printify_estimated_delivery', date('Y-m-d', $estimated_delivery));
        }
        
        // Add tracking info if available
        if (!empty($printify_order['shipments'])) {
            $this->updateTrackingInfo($order, $printify_order['shipments']);
        }
        
        // Add note
        $order->add_order_note(sprintf(
            __('Order imported from Printify (ID: %s)', 'wp-woocommerce-printify-sync'),
            $printify_order['id']
        ));
        
        // Save order
        $order->save();
        
        return $order->get_id();
    }
    
    /**
     * Add line items to order
     *
     * @param \WC_Order $order WooCommerce order
     * @param array $line_items Printify line items
     */
    private function addLineItems(\WC_Order $order, array $line_items): void {
        foreach ($line_items as $item) {
            // Try to find the product
            $product_id = $this->getWooProductIdByPrintifyId($item['product_id']);
            
            if (!$product_id) {
                // Product not found, create a simple placeholder product
                $this->logger->log("Product not found for Printify product ID {$item['product_id']}", 'warning');
                $product_id = $this->createPlaceholderProduct($item);
            }
            
            // Get variation if applicable
            $variation_id = 0;
            if (!empty($item['variant_id']) && !empty($product_id)) {
                $variation_id = $this->getWooVariationIdByPrintifyId($product_id, $item['variant_id']);
            }
            
            // Determine which product to use (variation or parent)
            $product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
            
            if (!$product) {
                $this->logger->log("Could not get product for line item", 'error');
                continue;
            }
            
            // Create line item
            $order_item = new \WC_Order_Item_Product();
            $order_item->set_product($product);
            $order_item->set_quantity($item['quantity']);
            
            // Set prices
            $retail_price = $item['price'] / 100; // Convert to dollars
            $cost_price = $item['cost'] / 100; // Convert to dollars
            
            $order_item->set_total($retail_price * $item['quantity']);
            $order_item->set_subtotal($retail_price * $item['quantity']);
            
            // Add item to order
            $order->add_item($order_item);
            
            // Store cost price as meta
            wc_add_order_item_meta($order_item->get_id(), '_printify_cost_price', $cost_price);
            wc_add_order_item_meta($order_item->get_id(), '_printify_item_id', $item['id']);
            
            if (!empty($item['variant_id'])) {
                wc_add_order_item_meta($order_item->get_id(), '_printify_variant_id', $item['variant_id']);
            }
        }
    }
    
    /**
     * Get WooCommerce product ID by Printify product ID
     *
     * @param string $printify_id Printify product ID
     * @return int|false WooCommerce product ID or false if not found
     */
    private function getWooProductIdByPrintifyId(string $printify_id) {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_printify_product_id' AND meta_value = %s LIMIT 1",
            $printify_id
        ));
        
        return $product_id ? (int) $product_id : false;
    }
    
    /**
     * Get WooCommerce variation ID by Printify variant ID
     *
     * @param int $product_id WooCommerce product ID
     * @param string $variant_id Printify variant ID
     * @return int|false WooCommerce variation ID or false if not found
     */
    private function getWooVariationIdByPrintifyId(int $product_id, string $variant_id) {
        global $wpdb;
        
        // First try direct match
        $variation_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_printify_variant_id' AND meta_value = %s LIMIT 1",
            $variant_id
        ));
        
        if ($variation_id) {
            return (int) $variation_id;
        }
        
        // No direct match, try looking through the parent product's variations
        $variations = get_posts([
            'post_type' => 'product_variation',
            'post_status' => 'publish',
            'post_parent' => $product_id,
            'posts_per_page' => -1
        ]);
        
        foreach ($variations as $variation) {
            $var_id = get_post_meta($variation->ID, '_printify_variant_id', true);
            
            if ($var_id === $variant_id) {
                return (int) $variation->ID;
            }
        }
        
        return false;
    }
    
    /**
     * Get WooCommerce order by Printify order ID
     *
     * @param string $printify_id Printify order ID
     * @return int|false WooCommerce order ID or false if not found
     */
    private function getWooOrderByPrintifyId(string $printify_id) {
        global $wpdb;
        
        if (HPOSCompat::isHPOSEnabled()) {
            // For HPOS
            $order_id = $wpdb->get_var($wpdb->prepare(
                "SELECT order_id FROM {$wpdb->prefix}wc_order_meta WHERE meta_key = '_printify_order_id' AND meta_value = %s LIMIT 1",
                $printify_id
            ));
        } else {
            // For traditional post meta
            $order_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_printify_order_id' AND meta_value = %s LIMIT 1",
                $printify_id
            ));
        }
        
        return $order_id ? (int) $order_id : false;
    }
    
    /**
     * Create a placeholder product for an unknown Printify product
     *
     * @param array $item Printify line item
     * @return int WooCommerce product ID
     */
    private function createPlaceholderProduct(array $item): int {
        // Create a simple product
        $product = new \WC_Product_Simple();
        
        $product->set_name($item['title'] ?? sprintf(__('Printify Product %s', 'wp-woocommerce-printify-sync'), $item['product_id']));
        $product->set_status('private'); // Not publicly visible
        $product->set_catalog_visibility('hidden');
        $product->set_regular_price($item['price'] / 100);
        
        // Save the product to get an ID
        $product_id = $product->save();
        
        // Set Printify metadata
        update_post_meta($product_id, '_printify_product_id', $item['product_id']);
        update_post_meta($product_id, '_printify_is_placeholder', '1');
        
        return $product_id;
    }
    
    /**
     * Update order tracking information
     *
     * @param \WC_Order $order WooCommerce order
     * @param array $shipments Printify shipments data
     */
    private function updateTrackingInfo(\WC_Order $order, array $shipments): void {
        if (empty($shipments)) {
            return;
        }
        
        // Get the latest shipment
        $shipment = end($shipments);
        
        // Store tracking info in order meta
        HPOSCompat::updateOrderMeta($order, '_printify_tracking_carrier', $shipment['carrier'] ?? '');
        HPOSCompat::updateOrderMeta($order, '_printify_tracking_number', $shipment['tracking_number'] ?? '');
        HPOSCompat::updateOrderMeta($order, '_printify_tracking_url', $shipment['tracking_url'] ?? '');
        
        // Add order note with tracking info
        if (!empty($shipment['tracking_number']) && !empty($shipment['carrier'])) {
            $tracking_note = sprintf(
                __('Order shipped via %1$s with tracking number %2$s', 'wp-woocommerce-printify-sync'),
                $shipment['carrier'],
                $shipment['tracking_number']
            );
            
            if (!empty($shipment['tracking_url'])) {
                $tracking_note .= sprintf(
                    __(' - <a href="%s" target="_blank">Track Package</a>', 'wp-woocommerce-printify-sync'),
                    esc_url($shipment['tracking_url'])
                );
            }
            
            $order->add_order_note($tracking_note, 1); // 1 = send to customer
        }
    }
    
    /**
     * Map Printify order status to WooCommerce status
     *
     * @param string $printify_status Printify order status
     * @return string WooCommerce order status
     */
    private function getWooStatusFromPrintify(string $printify_status): string {
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
    
    /**
     * Update order status based on Printify status
     *
     * @param \WC_Order $order WooCommerce order
     * @param string $printify_status Printify order status
     */
    private function updateOrderStatus(\WC_Order $order, string $printify_status): void {
        $woo_status = $this->getWooStatusFromPrintify($printify_status);
        
        // Skip if the status is the same
        if ($order->get_status() === $woo_status) {
            return;
        }
        
        // Update the order status
        $order->update_status(
            $woo_status,
            sprintf(__('Status updated from Printify: %s', 'wp-woocommerce-printify-sync'), $printify_status)
        );
        
        // Store the Printify status in order meta
        HPOSCompat::updateOrderMeta($order, '_printify_status', $printify_status);
        
        // Special handling for shipped status
        if ($printify_status === 'shipped' || $printify_status === 'on_the_way') {
            $order->update_status('completed', __('Order marked as completed because it has been shipped from Printify', 'wp-woocommerce-printify-sync'));
        }
    }
    
    /**
     * Sync WooCommerce order to Printify and handle responses
     *
     * @param int $wc_order_id WooCommerce order ID
     * @return bool Success
     */
    public function syncOrderToPrintify(int $wc_order_id): bool {
        $this->logger->log("Syncing WooCommerce order #{$wc_order_id} to Printify", 'info');
        
        try {
            $order = wc_get_order($wc_order_id);
            
            if (!$order) {
                throw new \Exception("WooCommerce order #{$wc_order_id} not found");
            }
            
            // Check if order is already linked to Printify
            $printify_id = HPOSCompat::getOrderMeta($order, '_printify_order_id');
            
            if ($printify_id) {
                $this->logger->log("Order already linked to Printify with ID {$printify_id}", 'info');
                return true;
            }
            
            // Prepare order data using dedicated formatter
            $order_data = $this->data_formatter->formatForPrintify($order);
            
            // Send to Printify
            $response = $this->api->createOrder($order_data);
            
            if (empty($response) || empty($response['id'])) {
                throw new \Exception("Invalid response from Printify API");
            }
            
            // Store order metadata using repository
            $this->order_repository->savePrintifyOrderData($order, $response);
            
            $this->logger->log("Order #{$wc_order_id} successfully synced to Printify with ID {$response['id']}", 'info');
            
            return true;
        } catch (\Exception $e) {
            $this->logger->log("Error syncing order #{$wc_order_id} to Printify: " . $e->getMessage(), 'error');
            
            if (isset($order)) {
                // Store error in order meta using repository
                $this->order_repository->saveOrderSyncError($order, $e->getMessage());
            }
            
            return false;
        }
    }
}
