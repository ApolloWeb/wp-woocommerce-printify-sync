<?php
/**
 * Order Helper class with HPOS compatibility
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */
 
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class OrderHelper extends BaseHelper {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if HPOS is active (for informational purposes)
     * Note: Our code works with or without HPOS enabled
     * 
     * @return bool True if HPOS is active
     */
    public function isHPOSActive() {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }
    
    /**
     * Get order by Printify order ID using HPOS-compatible approach
     *
     * @param string $printify_order_id Printify order ID
     * @return \WC_Order|false Order object or false
     */
    public function getOrderByPrintifyOrderId($printify_order_id) {
        // First try our mapping table (most efficient)
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwprintifysync_order_mapping';
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT order_id FROM {$table_name} WHERE printify_order_id = %s LIMIT 1",
            $printify_order_id
        ));
        
        if ($order_id) {
            return wc_get_order($order_id);
        }
        
        // If not found, use WooCommerce's data abstraction
        $orders = wc_get_orders([
            'limit' => 1,
            'meta_key' => '_printify_order_id',
            'meta_value' => $printify_order_id
        ]);
        
        return !empty($orders) ? reset($orders) : false;
    }
    
    /**
     * Submit order to Printify with HPOS compatibility
     *
     * @param int $order_id Order ID
     * @return bool Success status
     */
    public function submitOrderToPrintify($order_id) {
        // Get order using WC abstraction (works with or without HPOS)
        $order = wc_get_order($order_id);
        
        if (!$order) {
            LogHelper::getInstance()->error('Order not found', ['order_id' => $order_id]);
            return false;
        }
        
        // Check if order already submitted
        if ($order->get_meta('_printify_order_id')) {
            return false; // Already submitted
        }
        
        // Prepare order data for Printify
        $order_data = $this->prepareOrderData($order);
        
        // Get shop ID
        $shop_id = get_option('wpwprintifysync_shop_id', 0);
        
        // Send to Printify API
        $response = ApiHelper::getInstance()->sendPrintifyRequest(
            "shops/{$shop_id}/orders.json",
            [
                'method' => 'POST',
                'body' => $order_data
            ]
        );
        
        if (!$response['success']) {
            LogHelper::getInstance()->error('Failed to submit order to Printify', [
                'order_id' => $order_id,
                'error' => $response['message'] ?? 'Unknown error'
            ]);
            return false;
        }
        
        // Get Printify order ID from response
        $printify_order_id = $response['body']['id'] ?? '';
        
        if ($printify_order_id) {
            // Update order meta data using HPOS-compatible methods
            $order->update_meta_data('_printify_order_id', $printify_order_id);
            $order->update_meta_data('_printify_shop_id', $shop_id);
            $order->update_meta_data('_printify_submitted_at', $this->timestamp);
            $order->update_meta_data('_printify_submitted_by', $this->user);
            $order->save();
            
            // Add note to order
            $order->add_order_note(sprintf(
                __('Order submitted to Printify. Printify Order ID: %s', 'wp-woocommerce-printify-sync'),
                $printify_order_id
            ));
            
            // Also store in mapping table for faster lookups
            $this->storeOrderMapping($order_id, $printify_order_id, $shop_id);
            
            LogHelper::getInstance()->info('Order submitted to Printify', [
                'order_id' => $order_id,
                'printify_order_id' => $printify_order_id
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Store order mapping in custom table
     */
    private function storeOrderMapping($order_id, $printify_order_id, $shop_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwprintifysync_order_mapping';
        
        $wpdb->replace(
            $table_name,
            [
                'order_id' => $order_id,
                'printify_order_id' => $printify_order_id,
                'shop_id' => $shop_id,
                'status' => 'submitted',
                'created_at' => $this->timestamp,
                'updated_at' => $this->timestamp
            ]
        );
    }
    
    /**
     * Update order tracking info with HPOS compatibility
     *
     * @param string $printify_order_id Printify order ID
     * @param array $tracking_data Tracking data
     * @return bool Success status
     */
    public function updateOrderTracking($printify_order_id, $tracking_data) {
        $order = $this->getOrderByPrintifyOrderId($printify_order_id);
        
        if (!$order) {
            LogHelper::getInstance()->error('Order not found for tracking update', [
                'printify_order_id' => $printify_order_id
            ]);
            return false;
        }
        
        // Update tracking data using HPOS-compatible methods
        $order->update_meta_data('_printify_tracking_number', $tracking_data['tracking_number'] ?? '');
        $order->update_meta_data('_printify_tracking_url', $tracking_data['tracking_url'] ?? '');
        $order->update_meta_data('_printify_carrier', $tracking_data['carrier'] ?? '');
        $order->update_meta_data('_printify_tracking_updated_at', $this->timestamp);
        $order->save();
        
        // Add tracking note
        $tracking_note = __('Tracking information updated from Printify:', 'wp-woocommerce-printify-sync');
        if (!empty($tracking_data['tracking_number'])) {
            $tracking_note .= ' ' . $tracking_data['tracking_number'];
            
            if (!empty($tracking_data['tracking_url'])) {
                $tracking_note .= sprintf(' (<a href="%s" target="_blank">%s</a>)', 
                    esc_url($tracking_data['tracking_url']), 
                    __('Track Package', 'wp-woocommerce-printify-sync')
                );
            }
        }
        
        $order->add_order_note($tracking_note);
        
        return true;
    }
}