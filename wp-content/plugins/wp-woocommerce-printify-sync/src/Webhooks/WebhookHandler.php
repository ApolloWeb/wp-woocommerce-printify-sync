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
                $this->handleProductUpdate($data);
                break;
            
            case 'order.update':
                $this->handleOrderUpdate($data);
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
     * Handle product update webhook.
     *
     * @param array $data Webhook data.
     * @return void
     */
    private function handleProductUpdate($data) {
        if (empty($data['product_id'])) {
            $this->logger->error(
                'Failed to handle product update webhook: product_id is missing',
                ['data' => $data]
            );
            return;
        }
        
        // Schedule product import.
        as_schedule_single_action(time(), 'wpwps_import_product', [$data['product_id']], 'wpwps_product_import');
        
        $this->logger->info(
            sprintf('Product %s update scheduled via webhook', $data['product_id']),
            ['data' => $data]
        );
    }

    /**
     * Handle order update webhook.
     *
     * @param array $data Webhook data.
     * @return void
     */
    private function handleOrderUpdate($data) {
        // Find order by external_id or printify_id
        $order_id = $this->findOrderId($data);
        
        if (!$order_id) {
            $this->logger->error(
                'Failed to handle order update webhook: Could not find matching order',
                ['data' => $data]
            );
            return;
        }
        
        if (empty($data['status'])) {
            $this->logger->error(
                sprintf('Failed to handle order update webhook for order %s: status is missing', $order_id),
                ['data' => $data]
            );
            return;
        }
        
        // Update order status
        $this->order_sync->updateOrderStatus($order_id, $data['status']);
        
        $this->logger->info(
            sprintf('Order %s updated via webhook', $order_id),
            ['data' => $data]
        );
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
}
