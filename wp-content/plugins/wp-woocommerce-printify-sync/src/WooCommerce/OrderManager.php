<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce;

use ApolloWeb\WPWooCommercePrintifySync\API\Interfaces\PrintifyAPIInterface;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\CustomOrderStatuses;

/**
 * Handles synchronization of orders between WooCommerce and Printify
 */
class OrderManager
{
    private $printifyApi;
    private $customOrderStatuses;

    /**
     * Constructor
     *
     * @param PrintifyAPIInterface $printifyApi
     * @param CustomOrderStatuses $customOrderStatuses
     */
    public function __construct(PrintifyAPIInterface $printifyApi, CustomOrderStatuses $customOrderStatuses)
    {
        $this->printifyApi = $printifyApi;
        $this->customOrderStatuses = $customOrderStatuses;
    }

    /**
     * Get the Printify order ID for a WooCommerce order
     *
     * @param int $orderId WooCommerce order ID
     * @return string|null Printify order ID or null if not found
     */
    public function getPrintifyOrderId(int $orderId): ?string
    {
        $printifyOrderId = get_post_meta($orderId, '_printify_order_id', true);
        return !empty($printifyOrderId) ? $printifyOrderId : null;
    }

    /**
     * Get the WooCommerce order ID for a Printify order
     *
     * @param string $printifyOrderId
     * @return int|null WooCommerce order ID or null if not found
     */
    public function getWooOrderId(string $printifyOrderId): ?int
    {
        global $wpdb;
        
        $orderId = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_order_id' AND meta_value = %s",
            $printifyOrderId
        ));
        
        return $orderId ? (int) $orderId : null;
    }

    /**
     * Update a WooCommerce order with data from Printify
     *
     * @param int $orderId WooCommerce order ID
     * @param array $printifyOrder Printify order data
     * @return bool Success
     */
    public function updateWooOrderFromPrintify(int $orderId, array $printifyOrder): bool
    {
        try {
            // Update order status
            if (isset($printifyOrder['status'])) {
                $wcStatus = $this->customOrderStatuses->convertPrintifyStatusToWc($printifyOrder['status']);
                if ($wcStatus) {
                    $order = wc_get_order($orderId);
                    if ($order) {
                        // Remove 'printify-' prefix for standard WC statuses
                        $statusToSet = str_replace('printify-', '', $wcStatus);
                        $order->update_status($statusToSet, __('Updated via Printify Sync', 'wp-woocommerce-printify-sync'));
                    }
                }
            }
            
            // Update tracking information if available
            if (isset($printifyOrder['shipments']) && !empty($printifyOrder['shipments'])) {
                $shipment = $printifyOrder['shipments'][0]; // Get the first shipment
                if (isset($shipment['tracking_number']) && !empty($shipment['tracking_number'])) {
                    update_post_meta($orderId, '_printify_tracking_number', $shipment['tracking_number']);
                    update_post_meta($orderId, '_printify_carrier', $shipment['carrier'] ?? '');
                    update_post_meta($orderId, '_printify_shipping_date', $shipment['shipped_at'] ?? '');
                }
            }
            
            // Update last sync timestamp
            update_post_meta($orderId, '_printify_last_sync', current_time('mysql'));
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating WooCommerce order {$orderId} from Printify: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format Printify orders for display
     *
     * @param array $printifyOrders
     * @return array Formatted orders
     */
    public function formatOrdersForDisplay(array $printifyOrders): array
    {
        $formattedOrders = [];
        
        foreach ($printifyOrders as $order) {
            $wooOrderId = $this->getWooOrderId($order['id']);
            
            $formattedOrders[] = [
                'printify_order_id' => $order['id'],
                'wc_order_id' => $wooOrderId,
                'date' => isset($order['created_at']) ? date('Y-m-d H:i', strtotime($order['created_at'])) : 'N/A',
                'customer' => $order['address']['first_name'] . ' ' . $order['address']['last_name'],
                'status' => $order['status'],
                'total' => isset($order['total']) ? wc_price($order['total']) : 'N/A',
                'shipping_status' => !empty($order['shipments']) ? 'Shipped' : 'Awaiting Shipment',
                'is_synced' => !empty($wooOrderId)
            ];
        }
        
        return $formattedOrders;
    }

    /**
     * Sync an individual order from Printify to WooCommerce
     *
     * @param string $shopId Printify shop ID
     * @param string $printifyOrderId Printify order ID
     * @return int|false WooCommerce order ID or false on failure
     */
    public function syncOrderFromPrintify(string $shopId, string $printifyOrderId)
    {
        try {
            // Check if order already exists
            $wooOrderId = $this->getWooOrderId($printifyOrderId);
            
            // Get order details from Printify
            $printifyOrder = $this->printifyApi->getOrder($shopId, $printifyOrderId);
            
            if ($wooOrderId) {
                // Update existing order
                $this->updateWooOrderFromPrintify($wooOrderId, $printifyOrder);
                return $wooOrderId;
            } else {
                // Create a new order in WooCommerce
                $order = wc_create_order();
                
                // Set order data
                $order->set_customer_note($printifyOrder['notes'] ?? '');
                
                // Add order items
                foreach ($printifyOrder['line_items'] as $item) {
                    $product_id = wc_get_product_id_by_sku($item['sku']);
                    
                    if (!$product_id) {
                        // Try to find by Printify ID stored in meta
                        global $wpdb;
                        $product_id = $wpdb->get_var($wpdb->prepare(
                            "SELECT post_id FROM {$wpdb->postmeta} 
                            WHERE meta_key = '_printify_id' AND meta_value = %s",
                            $item['product_id']
                        ));
                    }
                    
                    if ($product_id) {
                        $order->add_product(wc_get_product($product_id), $item['quantity']);
                    } else {
                        // Add as a custom line item if product not found
                        $order->add_item([
                            'name' => $item['title'],
                            'qty' => $item['quantity'],
                            'total' => $item['price']
                        ]);
                    }
                }
                
                // Set address
                if (isset($printifyOrder['address'])) {
                    $address = $printifyOrder['address'];
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
                    
                    // Set shipping address (same as billing for now)
                    $order->set_address([
                        'first_name' => $address['first_name'] ?? '',
                        'last_name' => $address['last_name'] ?? '',
                        'company' => $address['company'] ?? '',
                        'address_1' => $address['address1'] ?? '',
                        'address_2' => $address['address2'] ?? '',
                        'city' => $address['city'] ?? '',
                        'state' => $address['state'] ?? '',
                        'postcode' => $address['zip'] ?? '',
                        'country' => $address['country'] ?? ''
                    ], 'shipping');
                }
                
                // Set status based on Printify status
                $wcStatus = $this->customOrderStatuses->convertPrintifyStatusToWc($printifyOrder['status']);
                $statusToSet = str_replace('printify-', '', $wcStatus);
                $order->set_status($statusToSet);
                
                // Set order meta data
                $order->update_meta_data('_printify_order_id', $printifyOrderId);
                $order->update_meta_data('_printify_shop_id', $shopId);
                $order->update_meta_data('_printify_last_sync', current_time('mysql'));
                
                // Add shipping tracking if available
                if (!empty($printifyOrder['shipments'])) {
                    $shipment = $printifyOrder['shipments'][0];
                    $order->update_meta_data('_printify_tracking_number', $shipment['tracking_number'] ?? '');
                    $order->update_meta_data('_printify_carrier', $shipment['carrier'] ?? '');
                    $order->update_meta_data('_printify_shipping_date', $shipment['shipped_at'] ?? '');
                }
                
                $order->calculate_totals();
                $order->save();
                
                return $order->get_id();
            }
        } catch (\Exception $e) {
            error_log("Error syncing Printify order {$printifyOrderId}: " . $e->getMessage());
            return false;
        }
    }
}
