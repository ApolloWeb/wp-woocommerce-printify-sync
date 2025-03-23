<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Core\HPOSCompat;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;

/**
 * Shipment Service
 * 
 * Handles shipment updates from Printify
 */
class ShipmentService {
    /**
     * @var PrintifyApiClient
     */
    private $api;
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var OrderSyncService
     */
    private $order_sync;
    
    /**
     * Constructor
     */
    public function __construct(PrintifyApiClient $api, Logger $logger, OrderSyncService $order_sync) {
        $this->api = $api;
        $this->logger = $logger;
        $this->order_sync = $order_sync;
        
        // Register hooks
        add_action('wpwps_process_shipment', [$this, 'processShipment']);
    }
    
    /**
     * Process a shipment update
     * 
     * @param string $printify_order_id Printify order ID
     * @return bool Success
     */
    public function processShipment(string $printify_order_id): bool {
        $this->logger->log("Processing shipment for Printify order {$printify_order_id}", 'info');
        
        try {
            // Get order from Printify
            $printify_order = $this->api->getOrder($printify_order_id);
            
            if (empty($printify_order)) {
                throw new \Exception("No data returned from Printify for order {$printify_order_id}");
            }
            
            // Get shipment data
            $shipments = $printify_order['shipments'] ?? [];
            
            if (empty($shipments)) {
                $this->logger->log("No shipment data found for Printify order {$printify_order_id}", 'warning');
                return false;
            }
            
            // Find WooCommerce order
            $order_id = $this->getWooOrderByPrintifyId($printify_order_id);
            
            if (!$order_id) {
                $this->logger->log("No matching WooCommerce order found for Printify order {$printify_order_id}", 'warning');
                
                // Try to import the order
                $order_id = $this->order_sync->importOrder($printify_order_id);
                
                if (!$order_id) {
                    throw new \Exception("Could not import order from Printify");
                }
            }
            
            $order = wc_get_order($order_id);
            
            if (!$order) {
                throw new \Exception("WooCommerce order #{$order_id} not found");
            }
            
            // Update tracking information
            $this->updateTrackingInfo($order, $shipments);
            
            // Update order status if needed
            if ($order->get_status() !== 'completed') {
                $order->update_status(
                    'completed',
                    __('Order marked as completed because it has been shipped from Printify', 'wp-woocommerce-printify-sync')
                );
            }
            
            $this->logger->log("Shipment processed successfully for order #{$order_id}", 'info');
            
            return true;
        } catch (\Exception $e) {
            $this->logger->log("Error processing shipment: " . $e->getMessage(), 'error');
            return false;
        }
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
        
        // Save the order
        $order->save();
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
}
