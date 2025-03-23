<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\OrderSyncService;
use ApolloWeb\WPWooCommercePrintifySync\Services\ProductSyncService;

/**
 * Handles incoming webhooks from Printify
 */
class WebhookHandler {
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var OrderSyncService
     */
    private $order_sync;
    
    /**
     * @var ProductSyncService
     */
    private $product_sync;
    
    /**
     * @var string Webhook secret for validation
     */
    private $webhook_secret;
    
    /**
     * Constructor
     */
    public function __construct(Logger $logger, OrderSyncService $order_sync, ProductSyncService $product_sync) {
        $this->logger = $logger;
        $this->order_sync = $order_sync;
        $this->product_sync = $product_sync;
        $this->webhook_secret = get_option('wpwps_webhook_secret');
        
        // Register webhook endpoint
        add_action('init', [$this, 'registerEndpoint']);
        add_action('template_redirect', [$this, 'handleRequest']);
    }
    
    /**
     * Register webhook endpoint
     */
    public function registerEndpoint(): void {
        add_rewrite_rule(
            'wpwps-webhook/?$',
            'index.php?wpwps-webhook=1',
            'top'
        );
        
        add_rewrite_tag('%wpwps-webhook%', '([^/]+)');
    }
    
    /**
     * Handle webhook request
     */
    public function handleRequest(): void {
        global $wp_query;
        
        if (!isset($wp_query->query_vars['wpwps-webhook'])) {
            return;
        }
        
        // Log webhook request
        $this->logger->log('Received webhook from Printify', 'info');
        
        // Verify request signature
        if (!$this->verifySignature()) {
            $this->logger->log('Invalid webhook signature', 'error');
            status_header(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }
        
        // Get payload
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if (empty($data)) {
            $this->logger->log('Empty webhook payload', 'error');
            status_header(400);
            echo json_encode(['error' => 'Empty payload']);
            exit;
        }
        
        // Process webhook based on event type
        $event = $data['event'] ?? '';
        $this->logger->log("Processing webhook event: {$event}", 'info');
        
        try {
            switch ($event) {
                case 'order.created':
                case 'order.updated':
                    $this->handleOrderWebhook($data);
                    break;
                    
                case 'product.created':
                case 'product.updated':
                    $this->handleProductWebhook($data);
                    break;
                    
                case 'order.canceled':
                    $this->handleOrderCancelWebhook($data);
                    break;
                    
                case 'shipping.update':
                    $this->handleShippingUpdateWebhook($data);
                    break;
                    
                default:
                    $this->logger->log("Unknown webhook event: {$event}", 'warning');
                    break;
            }
            
            // Successful response
            status_header(200);
            echo json_encode(['success' => true, 'message' => 'Webhook processed']);
        } catch (\Exception $e) {
            $this->logger->log("Error processing webhook: " . $e->getMessage(), 'error');
            status_header(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        
        exit;
    }
    
    /**
     * Verify webhook signature
     */
    private function verifySignature(): bool {
        if (empty($this->webhook_secret)) {
            // If no secret is set, skip verification (not recommended for production)
            return true;
        }
        
        $signature = $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] ?? '';
        
        if (empty($signature)) {
            return false;
        }
        
        $payload = file_get_contents('php://input');
        $expected_signature = hash_hmac('sha256', $payload, $this->webhook_secret);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Handle order webhook
     */
    private function handleOrderWebhook(array $data): void {
        $order_id = $data['data']['id'] ?? '';
        
        if (empty($order_id)) {
            throw new \Exception('Missing order ID in webhook data');
        }
        
        // Import/update the order in WooCommerce
        $wc_order_id = $this->order_sync->importOrder($order_id);
        
        if (!$wc_order_id) {
            throw new \Exception("Failed to import/update order {$order_id}");
        }
        
        $this->logger->log("Webhook processed: Order {$order_id} imported/updated as WooCommerce order #{$wc_order_id}", 'info');
    }
    
    /**
     * Handle product webhook
     */
    private function handleProductWebhook(array $data): void {
        $product_id = $data['data']['id'] ?? '';
        
        if (empty($product_id)) {
            throw new \Exception('Missing product ID in webhook data');
        }
        
        // First check if product exists by external_id
        $external_id = $data['data']['external_id'] ?? '';
        $wc_product_id = null;
        
        if (!empty($external_id) && is_numeric($external_id)) {
            $wc_product_id = absint($external_id);
            // Verify product exists
            $product = wc_get_product($wc_product_id);
            if (!$product) {
                $wc_product_id = null;
            }
        }
        
        // If not found by external_id, check by printify_product_id
        if (!$wc_product_id) {
            $wc_product_id = $this->getWooProductIdByPrintifyId($product_id);
        }
        
        // Import or update the product
        if ($wc_product_id) {
            // Update existing product
            $result = $this->product_sync->updateProduct($product_id, $wc_product_id);
            $action = 'updated';
        } else {
            // Import new product
            $wc_product_id = $this->product_sync->importProduct($product_id);
            $action = 'imported';
        }
        
        if (!$wc_product_id) {
            throw new \Exception("Failed to import/update product {$product_id}");
        }
        
        $this->logger->log("Webhook processed: Product {$product_id} {$action} as WooCommerce product #{$wc_product_id}", 'info');
    }
    
    /**
     * Handle order cancel webhook
     */
    private function handleOrderCancelWebhook(array $data): void {
        $order_id = $data['data']['id'] ?? '';
        
        if (empty($order_id)) {
            throw new \Exception('Missing order ID in webhook data');
        }
        
        // Find the WooCommerce order
        $wc_order_id = $this->getWooOrderByPrintifyId($order_id);
        
        if (!$wc_order_id) {
            $this->logger->log("WooCommerce order not found for Printify order {$order_id}", 'warning');
            return;
        }
        
        // Cancel the order
        $order = wc_get_order($wc_order_id);
        if ($order) {
            $order->update_status(
                'cancelled', 
                __('Order canceled in Printify', 'wp-woocommerce-printify-sync')
            );
            $this->logger->log("WooCommerce order #{$wc_order_id} canceled due to Printify cancellation", 'info');
        }
    }
    
    /**
     * Handle shipping update webhook
     */
    private function handleShippingUpdateWebhook(array $data): void {
        $order_id = $data['data']['order_id'] ?? '';
        
        if (empty($order_id)) {
            throw new \Exception('Missing order ID in webhook data');
        }
        
        // Find the WooCommerce order
        $wc_order_id = $this->getWooOrderByPrintifyId($order_id);
        
        if (!$wc_order_id) {
            $this->logger->log("WooCommerce order not found for Printify order {$order_id}", 'warning');
            return;
        }
        
        // Get the order
        $order = wc_get_order($wc_order_id);
        if (!$order) {
            throw new \Exception("WooCommerce order #{$wc_order_id} not found");
        }
        
        // Update tracking information
        $tracking = [
            [
                'carrier' => $data['data']['carrier'] ?? '',
                'tracking_number' => $data['data']['tracking_number'] ?? '',
                'tracking_url' => $data['data']['tracking_url'] ?? ''
            ]
        ];
        
        $order_sync = new OrderSyncService(null, $this->logger);
        $order_sync->updateTrackingInfo($order, $tracking);
        
        // Update order status
        $order_sync->updateOrderStatus($order, 'shipped');
        
        $this->logger->log("Shipping information updated for WooCommerce order #{$wc_order_id}", 'info');
    }
    
    /**
     * Get WooCommerce product ID by Printify product ID
     */
    private function getWooProductIdByPrintifyId(string $printify_id) {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_printify_product_id' AND meta_value = %s LIMIT 1",
            $printify_id
        ));
        
        return $product_id ? (int) $product_id : null;
    }
    
    /**
     * Get WooCommerce order ID by Printify order ID
     */
    private function getWooOrderByPrintifyId(string $printify_id) {
        global $wpdb;
        
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && 
            \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
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
}
