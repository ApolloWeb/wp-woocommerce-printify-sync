<?php
/**
 * Webhook Controller for Printify events
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Controllers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Controllers;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\LogHelper;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\OrderHelper;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\ProductHelper;

class WebhookController {
    private static $instance = null;
    private $timestamp = '2025-03-05 18:59:36';
    private $user = 'ApolloWeb';
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register webhook endpoint
        add_action('init', [$this, 'registerWebhookEndpoint']);
        add_action('wp_ajax_nopriv_wpwprintifysync_webhook', [$this, 'handleWebhook']);
    }
    
    /**
     * Register webhook endpoint
     */
    public function registerWebhookEndpoint() {
        add_rewrite_rule(
            'printify-webhook/([^/]+)/?$',
            'index.php?printify_webhook=1&webhook_action=$matches[1]',
            'top'
        );
        
        add_rewrite_tag('%printify_webhook%', '([0-1]+)');
        add_rewrite_tag('%webhook_action%', '([^&]+)');
        
        // Add listener for webhook
        add_action('template_redirect', [$this, 'listenForWebhook']);
    }
    
    /**
     * Listen for incoming webhooks
     */
    public function listenForWebhook() {
        if (get_query_var('printify_webhook') == '1') {
            $action = get_query_var('webhook_action');
            
            // Process the webhook
            $this->processWebhook($action);
            
            // Exit after processing
            exit;
        }
    }
    
    /**
     * Process webhook
     *
     * @param string $action Webhook action
     */
    private function processWebhook($action) {
        // Get raw POST data
        $raw_data = file_get_contents('php://input');
        
        // Verify webhook signature
        if (!$this->verifyWebhookSignature($raw_data, $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] ?? '')) {
            LogHelper::getInstance()->error('Invalid webhook signature');
            
            // Return 403 Forbidden
            status_header(403);
            exit;
        }
        
        // Parse JSON data
        $data = json_decode($raw_data, true);
        
        if (!is_array($data)) {
            LogHelper::getInstance()->error('Invalid webhook payload format', [
                'action' => $action
            ]);
            
            // Return 400 Bad Request
            status_header(400);
            exit;
        }
        
        LogHelper::getInstance()->info('Received webhook', [
            'action' => $action,
            'type' => $data['type'] ?? 'unknown',
            'timestamp' => $this->timestamp
        ]);
        
        // Process based on webhook type
        switch ($data['type'] ?? '') {
            case 'product.created':
            case 'product.updated':
                $this->handleProductWebhook($data);
                break;
                
            case 'product.deleted':
                $this->handleProductDeletedWebhook($data);
                break;
                
            case 'order.created':
            case 'order.updated':
                $this->handleOrderWebhook($data);
                break;
                
            case 'order.fulfilled':
            case 'order.shipped':
                $this->handleOrderShippedWebhook($data);
                break;
                
            case 'order.cancelled':
                $this->handleOrderCancelledWebhook($data);
                break;
                
            default:
                LogHelper::getInstance()->warning('Unhandled webhook type', [
                    'type' => $data['type'] ?? 'unknown'
                ]);
                break;
        }
        
        // Return 200 OK
        status_header(200);
        exit;
    }
    
    /**
     * Verify webhook signature
     *
     * @param string $payload Raw webhook payload
     * @param string $signature Signature from header
     * @return bool Valid signature
     */
    private function verifyWebhookSignature($payload, $signature) {
        $secret = get_option('wpwprintifysync_webhook_secret', '');
        
        if (empty($secret) || empty($signature)) {
            return false;
        }
        
        $expected_signature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Handle product webhook
     */
    private function handleProductWebhook($data) {
        if (empty($data['data']['product_id'])) {
            return;
        }
        
        $shop_id = $data['data']['shop_id'] ?? get_option('wpwprintifysync_shop_id');
        $product_id = $data['data']['product_id'];
        
        // Queue product import to avoid timeout
        wp_schedule_single_event(time(), 'wpwprintifysync_import_product', [
            'shop_id' => $shop_id,
            'product_id' => $product_id,
            'user' => $this->user,
            'timestamp' => $this->timestamp
        ]);
        
        LogHelper::getInstance()->info('Scheduled product import from webhook', [
            'product_id' => $product_id
        ]);
    }
    
    /**
     * Handle product deleted webhook
     */
    private function handleProductDeletedWebhook($data) {
        if (empty($data['data']['product_id'])) {
            return;
        }
        
        $printify_id = $data['data']['product_id'];
        
        // Get WooCommerce product
        $product_id = ProductHelper::getInstance()->getProductIdByPrintifyId($printify_id);
        
        if (!$product_id) {
            LogHelper::getInstance()->warning('Product not found for deletion', [
                'printify_id' => $printify_id
            ]);
            return;
        }
        
        // Get delete action from settings (trash or draft)
        $delete_action = get_option('wpwprintifysync_product_deletion', 'draft');
        
        if ($delete_action === 'trash') {
            wp_trash_post($product_id);
            LogHelper::getInstance()->info('Product trashed from webhook', [
                'product_id' => $product_id,
                'printify_id' => $printify_id
            ]);
        } else {
            // Set to draft
            wp_update_post([
                'ID' => $product_id,
                'post_status' => 'draft'
            ]);
            
            // Update product meta
            $product = wc_get_product($product_id);
            if ($product) {
                $product->update_meta_data('_printify_deleted_at', $this->timestamp);
                $product->update_meta_data('_printify_deleted_by', 'webhook-' . $this->user);
                $product->save();
            }
            
            LogHelper::getInstance()->info('Product set to draft from webhook', [
                'product_id' => $product_id,
                'printify_id' => $printify_id
            ]);
        }
    }
    
    /**
     * Handle order webhook
     */
    private function handleOrderWebhook($data) {
        if (empty($data['data']['order_id'])) {
            return;
        }
        
        $printify_order_id = $data['data']['order_id'];
        $external_id = $data['data']['external_id'] ?? null;
        
        // Find order in WooCommerce
        $order = null;
        
        if ($external_id) {
            $order = wc_get_order($external_id);
        }
        
        if (!$order) {
            $order = OrderHelper::getInstance()->getOrderByPrintifyOrderId($printify_order_id);
        }
        
        if (!$order) {
            LogHelper::getInstance()->warning('Order not found for webhook', [
                'printify_order_id' => $printify_order_id,
                'external_id' => $external_id
            ]);
            return;
        }
        
        // Update order meta
        $order->update_meta_data('_printify_webhook_received', $this->timestamp);
        $order->update_meta_data('_printify_status', $data['data']['status'] ?? 'unknown');
        $order->save();
        
        LogHelper::getInstance()->info('Order updated from webhook', [
            'order_id' => $order->get_id(),
            'printify_order_id' => $printify_order_id
        ]);
    }
    
    /**
     * Handle order shipped webhook
     */
    private function handleOrderShippedWebhook($data) {
        if (empty($data['data']['order_id'])) {
            return;
        }
        
        $printify_order_id = $data['data']['order_id'];
        
        // Find order in WooCommerce
        $order = OrderHelper::getInstance()->getOrderByPrintifyOrderId($printify_order_id);
        
        if (!$order) {
            LogHelper::getInstance()->warning('Order not found for shipping update', [
                'printify_order_id' => $printify_order_id
            ]);
            return;
        }
        
        // Get tracking information
        $tracking = [
            'tracking_number' => $data['data']['shipment']['tracking_number'] ?? '',
            'tracking_url' => $data['data']['shipment']['tracking_url'] ?? '',
            'carrier' => $data['data']['shipment']['carrier'] ?? ''
        ];
        
        // Update tracking info
        OrderHelper::getInstance()->updateOrderTracking($printify_order_id, $tracking);
        
        // Update order status
        $new_status = $data['type'] === 'order.fulfilled' ? 'printify-printed' : 'printify-shipped';
        $order->update_status($new_status, sprintf(
            __('Order status updated by Printify webhook: %s', 'wp-woocommerce-printify-sync'),
            $data['type']
        ));
        
        LogHelper::getInstance()->info('Order shipping updated from webhook', [
            'order_id' => $order->get_id(),
            'printify_order_id' => $printify_order_id,
            'status' => $new_status
        ]);
    }
    
    /**
     * Handle order cancelled webhook
     */
    private function handleOrderCancelledWebhook($data) {
        if (empty($data['data']['order_id'])) {
            return;
        }
        
        $printify_order_id = $data['data']['order_id'];
        
        // Find order in WooCommerce
        $order = OrderHelper::getInstance()->getOrderByPrintifyOrderId($printify_order_id);
        
        if (!$order) {
            LogHelper::getInstance()->warning('Order not found for cancellation', [
                'printify_order_id' => $printify_order_id
            ]);
            return;
        }
        
        // Update order meta
        $order->update_meta_data('_printify_cancelled_at', $this->timestamp);
        $order->update_meta_data('_printify_cancelled_by', 'printify-webhook');
        $order->save();
        
        // Add note
        $order->add_order_note(
            __('Order cancelled by Printify.', 'wp-woocommerce-printify-sync')
        );
        
        // Update order status if not already cancelled
        if ($order->get_status() !== 'cancelled') {
            $order->update_status('cancelled', __('Cancelled via Printify webhook', 'wp-woocommerce-printify-sync'));
        }
        
        LogHelper::getInstance()->info('Order cancelled from webhook', [
            'order_id' => $order->get_id(),
            'printify_order_id' => $printify_order_id
        ]);
    }
}