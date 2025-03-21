<?php
/**
 * Webhook Handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Webhooks
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

use ApolloWeb\WPWooCommercePrintifySync\Products\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderSync;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Webhook Handler class.
 */
class WebhookHandler {
    /**
     * ProductSync instance.
     *
     * @var ProductSync
     */
    private $product_sync;

    /**
     * OrderSync instance.
     *
     * @var OrderSync
     */
    private $order_sync;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ProductSync $product_sync ProductSync instance.
     * @param OrderSync   $order_sync   OrderSync instance.
     * @param Logger      $logger       Logger instance.
     */
    public function __construct(ProductSync $product_sync, OrderSync $order_sync, Logger $logger) {
        $this->product_sync = $product_sync;
        $this->order_sync = $order_sync;
        $this->logger = $logger;
    }

    /**
     * Initialize webhook handler.
     *
     * @return void
     */
    public function init() {
        add_action('rest_api_init', [$this, 'registerEndpoints']);
    }

    /**
     * Register webhook endpoints.
     *
     * @return void
     */
    public function registerEndpoints() {
        register_rest_route('wpwps/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handleWebhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Handle webhook request.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function handleWebhook($request) {
        $data = $request->get_json_params();
        
        if (empty($data)) {
            return rest_ensure_response([
                'success' => false,
                'message' => __('Invalid request data.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        $this->logger->info(
            'Received webhook',
            ['data' => $data]
        );
        
        if (!isset($data['type'])) {
            return rest_ensure_response([
                'success' => false,
                'message' => __('Invalid webhook type.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        switch ($data['type']) {
            case 'product.update':
            case 'product.created':
            case 'product.deleted':
                $this->handleProductWebhook($data);
                break;
            
            case 'order.update':
            case 'order.created':
            case 'order.cancelled':
                $this->handleOrderWebhook($data);
                break;
            
            default:
                $this->logger->info(
                    sprintf('Unhandled webhook type: %s', $data['type']),
                    ['data' => $data]
                );
                break;
        }
        
        return rest_ensure_response([
            'success' => true,
            'message' => __('Webhook processed.', 'wp-woocommerce-printify-sync'),
        ]);
    }

    /**
     * Handle product webhook.
     *
     * @param array $data Webhook data.
     * @return void
     */
    private function handleProductWebhook($data) {
        if (empty($data['product_id'])) {
            $this->logger->error(
                'Failed to handle product webhook: product_id is missing',
                ['data' => $data]
            );
            return;
        }
        
        $product_id = $data['product_id'];
        $event_type = $data['type'] ?? 'product.update';
        
        switch ($event_type) {
            case 'product.deleted':
                // Try to find the product in WooCommerce and delete it or mark it as trash
                $wc_product_id = $this->findProductByPrintifyId($product_id);
                if ($wc_product_id) {
                    wp_trash_post($wc_product_id);
                    $this->logger->info(
                        sprintf('Product %s trashed due to deletion in Printify', $product_id),
                        ['wc_product_id' => $wc_product_id]
                    );
                }
                break;
                
            case 'product.created':
            case 'product.update':
            default:
                // Schedule product import/update
                as_schedule_single_action(
                    time(), 
                    'wpwps_import_product', 
                    [$product_id], 
                    'wpwps_product_import'
                );
                
                $this->logger->info(
                    sprintf('Product %s update scheduled via webhook', $product_id),
                    ['event_type' => $event_type]
                );
                break;
        }
    }

    /**
     * Handle order webhook.
     *
     * @param array $data Webhook data.
     * @return void
     */
    private function handleOrderWebhook($data) {
        // Find order by external_id or printify_id
        $order_id = $this->findOrderId($data);
        
        if (!$order_id) {
            $this->logger->error(
                'Failed to handle order webhook: Could not find matching order',
                ['data' => $data]
            );
            return;
        }
        
        $event_type = $data['type'] ?? 'order.update';
        
        switch ($event_type) {
            case 'order.cancelled':
                // Update order status to cancelled
                $order = wc_get_order($order_id);
                if ($order) {
                    $order->update_status(
                        'cancelled',
                        __('Order cancelled in Printify', 'wp-woocommerce-printify-sync')
                    );
                    
                    $this->logger->info(
                        sprintf('Order %s cancelled via webhook', $order_id),
                        ['data' => $data]
                    );
                }
                break;
                
            case 'order.created':
            case 'order.update':
            default:
                // Update order status if available
                if (!empty($data['status'])) {
                    $this->order_sync->updateOrderStatus($order_id, $data['status']);
                    
                    $this->logger->info(
                        sprintf('Order %s updated via webhook', $order_id),
                        ['status' => $data['status']]
                    );
                }
                
                // Update shipping info if available
                if (!empty($data['shipping']) && !empty($data['shipping']['tracking_number'])) {
                    $this->updateOrderShippingInfo($order_id, $data['shipping']);
                }
                break;
        }
    }
    
    /**
     * Find WooCommerce product by Printify ID
     *
     * @param string $printify_id Printify product ID
     * @return int|false WooCommerce product ID or false if not found
     */
    private function findProductByPrintifyId($printify_id) {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' AND meta_value = %s 
            LIMIT 1",
            $printify_id
        ));
        
        return $product_id ? (int) $product_id : false;
    }
    
    /**
     * Find corresponding WooCommerce order ID
     *
     * @param array $data Webhook data.
     * @return int|false
     */
    private function findOrderId($data) {
        global $wpdb;
        
        // First check by external_id if available
        if (!empty($data['external_id'])) {
            $order_id = $data['external_id'];
            $order = wc_get_order($order_id);
            if ($order) {
                return $order_id;
            }
        }
        
        // Then check by printify_id
        if (!empty($data['id'])) {
            $meta_key = '_printify_order_id';
            $printify_id = $data['id'];
            
            $order_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                $meta_key,
                $printify_id
            ));
            
            if ($order_id) {
                return (int) $order_id;
            }
        }
        
        return false;
    }
    
    /**
     * Update order shipping information
     *
     * @param int   $order_id WooCommerce order ID
     * @param array $shipping Shipping information from Printify
     * @return void
     */
    private function updateOrderShippingInfo($order_id, $shipping) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Add tracking info as order meta
        if (!empty($shipping['tracking_number'])) {
            $order->update_meta_data('_printify_tracking_number', $shipping['tracking_number']);
        }
        
        if (!empty($shipping['carrier'])) {
            $order->update_meta_data('_printify_shipping_carrier', $shipping['carrier']);
        }
        
        if (!empty($shipping['method'])) {
            $order->update_meta_data('_printify_shipping_method', $shipping['method']);
        }
        
        if (!empty($shipping['url'])) {
            $order->update_meta_data('_printify_tracking_url', $shipping['url']);
        }
        
        // Add shipping note
        if (!empty($shipping['tracking_number']) && !empty($shipping['carrier'])) {
            $note = sprintf(
                __('Shipping info updated: %s tracking number %s', 'wp-woocommerce-printify-sync'),
                $shipping['carrier'],
                $shipping['tracking_number']
            );
            
            if (!empty($shipping['url'])) {
                $note .= sprintf(
                    __(' - <a href="%s" target="_blank">Track package</a>', 'wp-woocommerce-printify-sync'),
                    esc_url($shipping['url'])
                );
            }
            
            $order->add_order_note($note, true); // Add customer-facing note
        }
        
        $order->save();
    }
}
