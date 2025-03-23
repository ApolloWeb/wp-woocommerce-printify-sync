<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

use ApolloWeb\WPWooCommercePrintifySync\Core\HPOSCompat;

/**
 * Order Repository
 * 
 * Handles data storage and retrieval for orders
 */
class OrderRepository {
    /**
     * Get WooCommerce order by Printify order ID
     *
     * @param string $printify_id Printify order ID
     * @return int|null WooCommerce order ID or null if not found
     */
    public function getWooOrderByPrintifyId(string $printify_id): ?int {
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
        
        return $order_id ? (int) $order_id : null;
    }
    
    /**
     * Save Printify order data to WooCommerce order
     *
     * @param \WC_Order $order WooCommerce order
     * @param array $printify_data Printify order data
     * @return bool Success
     */
    public function savePrintifyOrderData(\WC_Order $order, array $printify_data): bool {
        // Store Printify order ID and other metadata
        HPOSCompat::updateOrderMeta($order, '_printify_order_id', $printify_data['id']);
        HPOSCompat::updateOrderMeta($order, '_printify_linked', '1');
        HPOSCompat::updateOrderMeta($order, '_printify_sync_status', 'synced');
        HPOSCompat::updateOrderMeta($order, '_printify_sync_date', current_time('mysql'));
        
        // Store full response for debugging if needed
        HPOSCompat::updateOrderMeta($order, '_printify_response', json_encode($printify_data));
        
        // Add order note
        $order->add_order_note(
            sprintf(__('Order synced to Printify (ID: %s)', 'wp-woocommerce-printify-sync'), $printify_data['id'])
        );
        
        return true;
    }
    
    /**
     * Save order sync error
     *
     * @param \WC_Order $order WooCommerce order
     * @param string $error_message Error message
     * @return bool Success
     */
    public function saveOrderSyncError(\WC_Order $order, string $error_message): bool {
        // Store error in order meta
        HPOSCompat::updateOrderMeta($order, '_printify_sync_status', 'failed');
        HPOSCompat::updateOrderMeta($order, '_printify_sync_error', $error_message);
        
        // Add order note
        $order->add_order_note(
            sprintf(__('Failed to sync order to Printify: %s', 'wp-woocommerce-printify-sync'), $error_message)
        );
        
        return true;
    }
    
    /**
     * Update tracking information
     *
     * @param \WC_Order $order WooCommerce order
     * @param array $shipments Shipment data
     * @return bool Success
     */
    public function updateTrackingInfo(\WC_Order $order, array $shipments): bool {
        if (empty($shipments)) {
            return false;
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
        
        return true;
    }
}
