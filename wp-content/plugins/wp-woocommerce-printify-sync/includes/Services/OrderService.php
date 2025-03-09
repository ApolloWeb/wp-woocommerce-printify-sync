<?php
/**
 * Order Service
 *
 * Handles communication between WooCommerce orders and Printify.
 * HPOS Compatible implementation.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\OrderServiceInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\ApiClientInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

/**
 * OrderService Class
 */
class OrderService implements OrderServiceInterface {
    /**
     * API client
     *
     * @var ApiClientInterface
     */
    protected $api_client;
    
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * Meta key for Printify order ID
     *
     * @var string
     */
    protected $printify_order_id_meta_key = '_printify_order_id';
    
    /**
     * Meta key for Printify tracking number
     *
     * @var string
     */
    protected $printify_tracking_meta_key = '_printify_tracking_number';
    
    /**
     * Constructor
     *
     * @param ApiClientInterface $api_client API client
     * @param LoggerInterface    $logger     Logger
     */
    public function __construct(ApiClientInterface $api_client, LoggerInterface $logger) {
        $this->api_client = $api_client;
        $this->logger = $logger;
    }
    
    /**
     * Get a WooCommerce order with HPOS compatibility
     *
     * @param int $order_id WooCommerce order ID
     * @return \WC_Order|false
     */
    protected function getOrder($order_id) {
        try {
            return wc_get_order($order_id);
        } catch (\Exception $e) {
            $this->logger->error("Failed to get order #{$order_id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send an order to Printify
     *
     * @param int $order_id WooCommerce order ID
     * @return array|WP_Error Printify response
     */
    public function sendOrderToPrintify($order_id) {
        // Get the WooCommerce order
        $order = $this->getOrder($order_id);
        
        if (!$order) {
            return new \WP_Error('invalid_order', sprintf(__('Order #%d could not be found', 'wp-woocommerce-printify-sync'), $order_id));
        }
        
        // Check if order has already been sent to Printify
        $printify_order_id = $this->getPrintifyOrderId($order_id);
        if ($printify_order_id) {
            $this->logger->info("Order #{$order_id} already sent to Printify as {$printify_order_id}", [
                'order_id' => $order_id,
                'printify_order_id' => $printify_order_id
            ]);
            return new \WP_Error('order_exists', sprintf(__('Order #%d already sent to Printify', 'wp-woocommerce-printify-sync'), $order_id));
        }
        
        // Check if order has Printify products
        if (!$this->hasPrintifyProducts($order_id)) {
            $this->logger->info("Order #{$order_id} has no Printify products, skipping", [
                'order_id' => $order_id
            ]);
            return new \WP_Error('no_printify_products', sprintf(__('Order #%d has no Printify products', 'wp-woocommerce-printify-sync'), $order_id));
        }
        
        // Build order data for Printify
        $order_data = $this->buildOrderData($order);
        
        // Send to Printify
        $this->logger->info("Sending order #{$order_id} to Printify", [
            'order_id' => $order_id,
            'order_data' => $order_data
        ]);
        
        $response = $this->api_client->createOrder($order_data);
        
        if (is_wp_error($response)) {
            $this->logger->error("Failed to create Printify order for #{$order_id}: " . $response->get_error_message(), [
                'order_id' => $order_id,
                'error' => $response->get_error_message()
            ]);
            return $response;
        }
        
        // Store Printify order ID in order meta
        if (isset($response['id'])) {
            $printify_order_id = $response['id'];
            $this->setPrintifyOrderId($order_id, $printify_order_id);
            
            $this->logger->info("Order #{$order_id} sent successfully to Printify as {$printify_order_id}", [
                'order_id' => $order_id,
                'printify_order_id' => $printify_order_id
            ]);
            
            // Add order note
            $order->add_order_note(
                sprintf(
                    __('Order sent to Printify. Printify Order ID: %s', 'wp-woocommerce-printify-sync'),
                    $printify_order_id
                )
            );
        }
        
        return $response;
    }
    
    /**
     * Build Printify order data from WooCommerce order
     *
     * @param \WC_Order $order WooCommerce order
     * @return array
     */
    protected function buildOrderData($order) {
        // Basic order data
        $data = [
            'external_id' => $order->get_id(),
            'label'       => $order->get_order_number(),
            'line_items'  => $this->getOrderLineItems($order),
            'shipping_address' => $this->getShippingAddress($order),
            'shipping_method' => $this->getShippingMethod($order),
        ];
        
        // Add send shipping notifications flag if enabled in settings
        $data['send_shipping_notification'] = 'yes' === get_option('apolloweb_printify_send_shipping_notifications', 'yes');
        
        return $data;
    }
    
    /**
     * Get line items from WooCommerce order for Printify
     *
     * @param \WC_Order $order WooCommerce order
     * @return array
     */
    protected function getOrderLineItems($order) {
        $line_items = [];
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            
            // Use variation ID if available
            $actual_product_id = $variation_id ? $variation_id : $product_id;
            
            // Get the product
            $product = wc_get_product($actual_product_id);
            
            if (!$product) {
                continue;
            }
            
            // Check if this is a Printify product
            $printify_product_id = get_post_meta($product_id, '_printify_product_id', true);
            $printify_variant_id = '';
            
            // For variations, get variant ID
            if ($variation_id && $printify_product_id) {
                $printify_variant_id = get_post_meta($variation_id, '_printify_variant_id', true);
            }
            
            // Skip non-Printify products
            if (!$printify_product_id) {
                continue;
            }
            
            // Add to line items
            $line_items[] = [
                'product_id'  => $printify_product_id,
                'variant_id'  => $printify_variant_id,
                'quantity'    => $item->get_quantity(),
            ];
        }
        
        return $line_items;
    }
    
    /**
     * Get shipping address from WooCommerce order
     *
     * @param \WC_Order $order WooCommerce order
     * @return array
     */
    protected function getShippingAddress($order) {
        return [
            'first_name'    => $order->get_shipping_first_name() ?: $order->get_billing_first_name(),
            'last_name'     => $order->get_shipping_last_name() ?: $order->get_billing_last_name(),
            'email'         => $order->get_billing_email(),
            'phone'         => $order->get_billing_phone(),
            'address1'      => $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
            'address2'      => $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
            'city'          => $order->get_shipping_city() ?: $order->get_billing_city(),
            'state'         => $order->get_shipping_state() ?: $order->get_billing_state(),
            'zip'           => $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
            'country'       => $order->get_shipping_country() ?: $order->get_billing_country(),
            'company'       => $order->get_shipping_company() ?: $order->get_billing_company(),
        ];
    }
    
    /**
     * Get shipping method information
     *
     * @param \WC_Order $order WooCommerce order
     * @return array
     */
    protected function getShippingMethod($order) {
        $shipping_methods = $order->get_shipping_methods();
        $shipping_method = reset($shipping_methods);
        
        // Default to standard shipping
        $method_id = 'standard';
        
        if ($shipping_method) {
            // Try to map WooCommerce shipping method to Printify shipping method
            $method_id = $this->mapShippingMethod($shipping_method->get_method_id());
        }
        
        return [
            'id' => $method_id
        ];
    }
    
    /**
     * Map WooCommerce shipping method to Printify shipping method
     *
     * @param string $wc_method_id WooCommerce method ID
     * @return string Printify method ID
     */
    protected function mapShippingMethod($wc_method_id) {
        $mapping = [
            'free_shipping'       => 'standard',
            'flat_rate'           => 'standard',
            'local_pickup'        => 'standard',
            'priority_shipping'   => 'priority',
            'express_shipping'    => 'express',
        ];
        
        // Allow custom mapping via filter
        $mapping = apply_filters('apolloweb_printify_shipping_method_mapping', $mapping);
        
        return isset($mapping[$wc_method_id]) ? $mapping[$wc_method_id] : 'standard';
    }
    
    /**
     * Cancel an order in Printify
     *
     * @param int $order_id WooCommerce order ID
     * @return array|WP_Error Printify response
     */
    public function cancelOrderInPrintify($order_id) {
        // Get Printify order ID
        $printify_order_id = $this->getPrintifyOrderId($order_id);
        
        if (!$printify_order_id) {
            return new \WP_Error('no_printify_order', sprintf(__('No Printify order found for order #%d', 'wp-woocommerce-printify-sync'), $order_id));
        }
        
        // Get the order
        $order = $this->getOrder($order_id);
        
        if (!$order) {
            return new \WP_Error('invalid_order', sprintf(__('Order #%d could not be found', 'wp-woocommerce-printify-sync'), $order_id));
        }
        
        // Send cancel request to Printify
        $this->logger->info("Cancelling Printify order {$printify_order_id} for WooCommerce order #{$order_id}", [
            'order_id' => $order_id,
            'printify_order_id' => $printify_order_id
        ]);
        
        $response = $this->api_client->cancelOrder($printify_order_id);
        
        if (is_wp_error($response)) {
            $this->logger->error("Failed to cancel Printify order {$printify_order_id}: " . $response->get_error_message(), [
                'order_id' => $order_id,
                'printify_order_id' => $printify_order_id,
                'error' => $response->get_error_message()
            ]);
            
            return $response;
        }
        
        // Add order note
        $order->add_order_note(
            sprintf(
                __('Order cancelled in Printify. Printify Order ID: %s', 'wp-woocommerce-printify-sync'),
                $printify_order_id
            )
        );
        
        $this->logger->info("Successfully cancelled Printify order {$printify_order_id}", [
            'order_id' => $order_id,
            'printify_order_id' => $printify_order_id
        ]);
        
        return $response;
    }
    
    /**
     * Update a WooCommerce order based on Printify order data
     *
     * @param array $printify_order_data Order data from Printify
     * @return bool Success
     */
    public function updateOrderFromPrintify($printify_order_data) {
        // Check if we have the external_id to identify the WooCommerce order
        if (!isset($printify_order_data['external_id'])) {
            $this->logger->error("Missing external_id in Printify order data", [
                'printify_order_data' => $printify_order_data
            ]);
            return false;
        }
        
        $order_id = $printify_order_data['external_id'];
        $order = $this->getOrder($order_id);
        
        if (!$order) {
            $this->logger->error("WooCommerce order #{$order_id} not found for Printify update", [
                'order_id' => $order_id
            ]);
            return false;
        }
        
        // Store Printify order ID if not already stored
        if (isset($printify_order_data['id'])) {
            $this->setPrintifyOrderId($order_id, $printify_order_data['id']);
        }
        
        // Update order status based on Printify status
        if (isset($printify_order_data['status'])) {
            $this->updateOrderStatus($order, $printify_order_data['status']);
        }
        
        // Update tracking information if available
        if (isset($printify_order_data['shipments']) && !empty($printify_order_data['shipments'])) {
            $this->updateOrderTracking($order, $printify_order_data['shipments']);
        }
        
        $this->logger->info("Updated WooCommerce order #{$order_id} from Printify", [
            'order_id' => $order_id,
            'printify_order_id' => $printify_order_data['id'] ?? 'unknown'
        ]);
        
        return true;
    }
    
    /**
     * Update WooCommerce order status based on Printify status
     *
     * @param \WC_Order $order WooCommerce order
     * @param string    $printify_status Printify order status
     * @return void
     */
    protected function updateOrderStatus($order, $printify_status) {
        // Map Printify status to WooCommerce status
        $status_mapping = [
            'pending'    => 'processing',
            'on-hold'    => 'on-hold',
            'canceled'   => 'cancelled',
            'fulfilled'  => 'completed',
        ];
        
        // Allow custom mapping via filter
        $status_mapping = apply_filters('apolloweb_printify_status_mapping', $status_mapping);
        
        if (isset($status_mapping[$printify_status])) {
            $new_status = $status_mapping[$printify_status];
            $order_id = $order->get_id();
            
            $this->logger->info("Updating order #{$order_id} status to {$new_status} based on Printify status {$printify_status}", [
                'order_id' => $order_id,
                'new_status' => $new_status,
                'printify_status' => $printify_status
            ]);
            
            $order->update_status(
                $new_status,
                sprintf(
                    __('Status updated by Printify - Printify status: %s', 'wp-woocommerce-printify-sync'),
                    $printify_status
                )
            );
        }
    }
    
    /**
     * Update order tracking information
     *
     * @param \WC_Order $order WooCommerce order
     * @param array     $shipments Shipment data from Printify
     * @return void
     */
    protected function updateOrderTracking($order, $shipments) {
        if (empty($shipments)) {
            return;
        }
        
        // Process the first shipment (most common case)
        $shipment = $shipments[0];
        $order_id = $order->get_id();
        
        if (isset($shipment['tracking_number']) && isset($shipment['carrier'])) {
            // Store tracking info as order meta with HPOS compatibility
            $order->update_meta_data($this->printify_tracking_meta_key, $shipment['tracking_number']);
            $order->update_meta_data('_printify_carrier', $shipment['carrier']);
            
            if (isset($shipment['tracking_url'])) {
                $order->update_meta_data('_printify_tracking_url', $shipment['tracking_url']);
            }
            
            $order->save();
            
            // Add a note to the order
            $tracking_url = isset($shipment['tracking_url']) ? $shipment['tracking_url'] : '';
            $note = sprintf(
                __('Tracking information: %1$s - %2$s %3$s', 'wp-woocommerce-printify-sync'),
                $shipment['carrier'],
                $shipment['tracking_number'],
                !empty($tracking_url) ? "(<a href='{$tracking_url}' target='_blank'>Track</a>)" : ''
            );
            $order->add_order_note($note);
            
            $this->logger->info("Updated tracking information for order #{$order_id}: {$shipment['carrier']} - {$shipment['tracking_number']}", [
                'order_id' => $order_id,
                'tracking_number' => $shipment['tracking_number'],
                'carrier' => $shipment['carrier']
            ]);
            
            // Send tracking email if enabled
            if ('yes' === get_option('apolloweb_printify_send_tracking_email', 'yes')) {
                do_action('apolloweb_printify_send_tracking_email', $order_id);
            }
        }
    }
    
    /**
     * Get Printify order ID from WooCommerce order
     *
     * @param int $order_id WooCommerce order ID
     * @return string|false Printify order ID or false if not found
     */
    public function getPrintifyOrderId($order_id) {
        $order = $this->getOrder($order_id);
        
        if (!$order) {
            return false;
        }
        
        return $order->get_meta($this->printify_order_id_meta_key, true);
    }
    
    /**
     * Set Printify order ID for a WooCommerce order
     *
     * @param int    $order_id WooCommerce order ID
     * @param string $printify_order_id Printify order ID
     * @return bool
     */
    public function setPrintifyOrderId($order_id, $printify_order_id) {
        $order = $this->getOrder($order_id);
        
        if (!$order) {
            return false;
        }
        
        $order->update_meta_data($this->printify_order_id_meta_key, $printify_order_id);
        $order->save();
        
        return true;
    }
    
    /**
     * Check if an order contains Printify products
     *
     * @param int $order_id WooCommerce order ID
     * @return bool
     */
    public function hasPrintifyProducts($order_id) {
        $order = $this->getOrder($order_id);
        
        if (!$order) {
            return false;
        }
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            // Check if this is a Printify product
            $printify_product_id = get_post_meta($product_id, '_printify_product_id', true);
            
            if ($printify_product_id) {
                return true;
            }
        }
        
        return false;
    }
}