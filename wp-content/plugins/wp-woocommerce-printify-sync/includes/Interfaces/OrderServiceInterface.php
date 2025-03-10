<?php
/**
 * Order Service Interface
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Interfaces
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Interfaces;

/**
 * OrderServiceInterface Interface
 */
interface OrderServiceInterface {
    /**
     * Send an order to Printify
     *
     * @param int $order_id WooCommerce order ID
     * @return mixed
     */
    public function sendOrderToPrintify($order_id);
    
    /**
     * Cancel an order in Printify
     *
     * @param int $order_id WooCommerce order ID
     * @return mixed
     */
    public function cancelOrderInPrintify($order_id);
    
    /**
     * Update a WooCommerce order based on Printify order data
     *
     * @param array $printify_order_data Order data from Printify
     * @return mixed
     */
    public function updateOrderFromPrintify($printify_order_data);
    
    /**
     * Get Printify order ID from WooCommerce order
     *
     * @param int $order_id WooCommerce order ID
     * @return string|false Printify order ID or false if not found
     */
    public function getPrintifyOrderId($order_id);
    
    /**
     * Set Printify order ID for a WooCommerce order
     *
     * @param int    $order_id WooCommerce order ID
     * @param string $printify_order_id Printify order ID
     * @return bool
     */
    public function setPrintifyOrderId($order_id, $printify_order_id);
    
    /**
     * Check if an order contains Printify products
     *
     * @param int $order_id WooCommerce order ID
     * @return bool
     */
    public function hasPrintifyProducts($order_id);
}