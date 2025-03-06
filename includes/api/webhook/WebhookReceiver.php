<?php
/**
 * Webhook Receiver
 *
 * Handles incoming webhook requests from Printify.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API\Webhook
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API\Webhook;

use ApolloWeb\WPWooCommercePrintifySync\API\Printify\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Products\ProductManager;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderManager;
use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WebhookReceiver {
    /**
     * Singleton instance
     *
     * @var WebhookReceiver
     */
    private static $instance = null;
    
    /**
     * Namespace
     *
     * @var string
     */
    private $namespace = 'wpwprintifysync/v1';
    
    /**
     * Route
     *
     * @var string
     */
    private $route = '/webhook';
    
    /**
     * Current timestamp
     *
     * @var string
     */
    private $timestamp;
    
    /**
     * Current user
     *
     * @var string
     */
    private $user;
    
    /**
     * Get singleton instance
     *
     * @return WebhookReceiver
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->timestamp = '2025-03-05 19:50:58';
        $this->user = 'ApolloWeb';
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route($this->namespace, $this->route, array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Public endpoint, security handled within the method
        ));
    }
    
    /**
     * Handle webhook request
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response Response object
     */
    public function handle_webhook($request) {
        // Get request payload
        $payload = $request->get_body();
        
        // Get request headers
        $headers = $request->get_headers();
        
        // Verify webhook signature
        $signature = isset($headers['x_printify_signature']) ? $headers['x_printify_signature'][0] : '';
        if (!$this->verify_signature($payload, $signature)) {
            Logger::get_instance()->warning('Invalid webhook signature', array(
                'timestamp' => $this->timestamp,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ));
            
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Invalid signature'
            ));
        }
        
        // Parse payload
        $data = json_decode($payload, true);
        if (empty($data) || !isset($data['type'])) {
            Logger::get_instance()->warning('Invalid webhook payload', array(
                'timestamp' => $this->timestamp,
                'payload' => substr($payload, 0, 255) // Log only first 255 chars for brevity
            ));
            
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Invalid payload'
            ));
        }
        
        // Extract additional parameters if provided in query string
        $params = $request->get_params();
        $shop_id = isset($params['shop_id']) ? intval($params['shop_id']) : 
                   (isset($data['shop_id']) ? intval($data['shop_id']) : 0);
        
        // If shop_id is missing, try to get from settings
        if (empty($shop_id)) {
            $shop_id = get_option('wpwprintifysync_settings')['printify_shop_id'] ?? 0;
        }
        
        // Process event
        $result = $this->process_event($data, $shop_id);
        
        // Log webhook receipt
        Logger::get_instance()->info('Webhook processed', array(
            'type' => $data['type'],
            'success' => $result['success'],
            'shop_id' => $shop_id,
            'timestamp' => $this->timestamp
        ));
        
        return rest_ensure_response($result);
    }
    
    /**
     * Process webhook event
     *
     * @param array $data Event data
     * @param int $shop_id Shop ID
     * @return array Response data
     */
    private function process_event($data, $shop_id) {
        $type = $data['type'] ?? '';
        
        switch ($type) {
            case 'product.published':
                return $this->handle_product_published($data, $shop_id);
                
            case 'product.updated':
                return $this->handle_product_updated($data, $shop_id);
                
            case 'product.unpublished':
                return $this->handle_product_unpublished($data, $shop_id);
                
            case 'order.created':
                return $this->handle_order_created($data, $shop_id);
                
            case 'order.updated':
                return $this->handle_order_updated($data, $shop_id);
                
            case 'shipping.update':
                return $this->handle_shipping_update($data, $shop_id);
                
            case 'order.fulfilled':
                return $this->handle_order_fulfilled($data, $shop_id);
                
            default:
                Logger::get_instance()->notice('Unhandled webhook event', array(
                    'type' => $type,
                    'timestamp' => $this->timestamp
                ));
                
                return array(
                    'success' => true,
                    'message' => 'Event ignored'
                );
        }
    }
    
    /**
     * Handle product published event
     *
     * @param array $data Event data
     * @param int $shop_id Shop ID
     * @return array Response data
     */
    private function handle_product_published($data, $shop_id) {
        $product_id = $data['product']['id'] ?? '';
        
        if (empty($product_id)) {
            return array(
                'success' => false,
                'message' => 'Missing product ID'
            );
        }
        
        // Get full product data from Printify API
        $api_client = PrintifyApiClient::get_instance();
        $response = $api_client->request("shops/{$shop_id}/products/{$product_id}.json");
        
        if (!$response['success']) {
            Logger::get_instance()->error('Failed to fetch product data', array(
                'product_id' => $product_id,
                'error' => $response['message'],
                'timestamp' => $this->timestamp
            ));
            
            return array(
                'success' => false,
                'message' => 'Failed to fetch product data'
            );
        }
        
        // Import product
        $product_manager = ProductManager::get_instance();
        $wc_product_id = $product_manager->import_product($response['body'], $shop_id);
        
        if ($wc_product_id) {
            return array(
                'success' => true,
                'message' => 'Product published successfully',
                'product_id' => $wc_product_id
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to import product'
            );
        }
    }
    
    /**
     * Handle product updated event
     *
     * @param array $data Event data
     * @param int $shop_id Shop ID
     * @return array Response data
     */
    private function handle_product_updated($data, $shop_id) {
        $product_id = $data['product']['id'] ?? '';
        
        if (empty($product_id)) {
            return array(
                'success' => false,
                'message' => 'Missing product ID'
            );
        }
        
        // Get full product data from Printify API
        $api_client = PrintifyApiClient::get_instance();
        $response = $api_client->request("shops/{$shop_id}/products/{$product_id}.json");
        
        if (!$response['success']) {
            Logger::get_instance()->error('Failed to fetch product data', array(
                'product_id' => $product_id,
                'error' => $response['message'],
                'timestamp' => $this->timestamp
            ));
            
            return array(
                'success' => false,
                'message' => 'Failed to fetch product data'
            );
        }
        
        // Update product
        $product_manager = ProductManager::get_instance();
        $wc_product_id = $product_manager->update_product($response['body'], $shop_id);
        
        if ($wc_product_id) {
            return array(
                'success' => true,
                'message' => 'Product updated successfully',
                'product_id' => $wc_product_id
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to update product'
            );
        }
    }
    
    /**
     * Handle product unpublished event
     *
     * @param array $data Event data
     * @param int $shop_id Shop ID
     * @return array Response data
     */
    private function handle_product_unpublished($data, $shop_id) {
        $product_id = $data['product']['id'] ?? '';
        
        if (empty($product_id)) {
            return array(
                'success' => false,
                'message' => 'Missing product ID'
            );
        }
        
        // Unpublish product in WooCommerce
        $product_manager = ProductManager::get_instance();
        $result = $product_manager->unpublish_product($product_id);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Product unpublished successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to unpublish product'
            );
        }
    }
    
    /**
     * Handle order created event
     *
     * @param array $data Event data
     * @param int $shop_id Shop ID
     * @return array Response data
     */
    private function handle_order_created($data, $shop_id) {
        $order_id = $data['order']['id'] ?? '';
        
        if (empty($order_id)) {
            return array(
                'success' => false,
                'message' => 'Missing order ID'
            );
        }
        
        // Process order in OrderManager
        $order_manager = OrderManager::get_instance();
        $result = $order_manager->process_printify_order($order_id, $shop_id);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Order processed successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to process order'
            );
        }
    }
    
    /**
     * Handle order updated event
     *
     * @param array $data Event data
     * @param int $shop_id Shop ID
     * @return array Response data
     */
    private function handle_order_updated($data, $shop_id) {
        $order_id = $data['order']['id'] ?? '';
        
        if (empty($order_id)) {
            return array(
                'success' => false,
                'message' => 'Missing order ID'
            );
        }
        
        // Update order in OrderManager
        $order_manager = OrderManager::get_instance();
        $result = $order_manager->update_printify_order($order_id, $shop_id);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Order updated successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to update order'
            );
        }
    }
    
    /**
     * Handle shipping update event
     *
     * @param array $data Event data
     * @param int $shop_id Shop ID
     * @return array Response data
     */
    private function handle_shipping_update($data, $shop_id) {
        $order_id = $data['order']['id'] ?? '';
        $tracking_info = $data['tracking'] ?? null;
        
        if (empty($order_id) || empty($tracking_info)) {
            return array(
                'success' => false,
                'message' => 'Missing order ID or tracking information'
            );
        }
        
        // Update tracking in OrderManager
        $order_manager = OrderManager::get_instance();
        $result = $order_manager->update_tracking($order_id, $tracking_info, $shop_id);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Tracking updated successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to update tracking'
            );
        }
    }
    
    /**
     * Handle order fulfilled event
     *
     * @param array $data Event data
     * @param int $shop_id Shop ID
     * @return array Response data
     */
    private function handle_order_fulfilled($data, $shop_id) {
        $order_id = $data['order']['id'] ?? '';
        
        if (empty($order_id)) {
            return array(
                'success' => false,
                'message' => 'Missing order ID'
            );
        }
        
        // Mark order as completed in OrderManager
        $order_manager = OrderManager::get_instance();
        $result = $order_manager->complete_order($order_id, $shop_id);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Order marked as fulfilled'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to mark order as fulfilled'
            );
        }
    }
    
    /**
     * Verify webhook signature
     *
     * @param string $payload Request payload
     * @param string $signature Request signature
     * @return bool Whether the signature is valid
     */
    private function verify_signature($payload, $signature) {
        // Get webhook secret
        $secret = get_option('wpwprintifysync_webhook_secret');
        
        // If no secret is set, don't verify signature
        if (empty($secret) || empty($signature)) {
            return true;
        }
        
        // Calculate expected signature
        $expected_signature = hash_hmac('sha256', $payload, $secret);
        
        // Compare signatures
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Register webhook with Printify API
     *
     * @param int $shop_id Shop ID
     * @return array Response data
     */
    public function register_webhook($shop_id) {
        if (empty($shop_id)) {
            return array(
                'success' => false,
                'message' => 'Shop ID is required'
            );
        }
        
        // Generate webhook secret if not exists
        $webhook_secret = get_option('wpwprintifysync_webhook_secret');
        if (empty($webhook_secret)) {
            $webhook_secret = wp_generate_password(32, false);
            update_option('wpwprintifysync_webhook_secret', $webhook_secret);
        }
        
        // Get webhook endpoint URL
        $webhook_url = rest_url($this->namespace . $this->route);
        
        // Add shop_id to webhook URL
        $webhook_url = add_query_arg('shop_id', $shop_id, $webhook_url);
        
        // Check for existing webhook
        $webhook_id = get_option("wpwprintifysync_webhook_id_{$shop_id}");
        
        // Define events to listen for
        $events = array(
            'product.published',
            'product.updated',
            'product.unpublished',
            'order.created',
            'order.updated',
            'shipping.update',
            'order.fulfilled'
        );
        
        // Call Printify API
        $api_client = PrintifyApiClient::get_instance();
        
        if ($webhook_id) {
            // Update existing webhook
            $response = $api_client->request("shops/{$shop_id}/webhooks/{$webhook_id}.json", array(
                'method' => 'PUT',
                'body' => array(
                    'url' => $webhook_url,
                    'secret' => $webhook_secret,
                    'events' => $events,
                    'enabled' => true,
                    'metadata' => array(
                        'version' => WPWPRINTIFYSYNC_VERSION,
                        'timestamp' => $this->timestamp
                    )
                )
            ));
        } else {
            // Create new webhook
            $response = $api_client->request("shops/{$shop_id}/webhooks.json", array(
                'method' => 'POST',
                'body' => array(
                    'url' => $webhook_url,
                    'secret' => $webhook_secret,
                    'events' => $events,
                    'enabled' => true,
                    'metadata' => array(
                        'version' => WPWPRINTIFYSYNC_VERSION,
                        'timestamp' => $this->timestamp
                    )
                )
            ));
        }
        
        if ($response['success'] && isset($response['body']['id'])) {
            // Save webhook ID
            update_option("wpwprintifysync_webhook_id_{$shop_id}", $response['body']['id']);
            
            // Set webhook status
            set_transient('wpwprintifysync_webhook_status', 'active', HOUR_IN_SECONDS * 12);
            
            Logger::get_instance()->info('Webhook registered successfully', array(
                'shop_id' => $shop_id,
                'webhook_id' => $response['body']['id'],
                'timestamp' => $this->timestamp
            ));
            
            return array(
                'success' => true,
                'message' => 'Webhook registered successfully',
                'webhook_id' => $response['body']['id']
            );
        } else {
            // Set webhook status
            set_transient('wpwprintifysync_webhook_status', 'error', HOUR_IN_SECONDS * 12);
            
            Logger::get_instance()->error('Failed to register webhook', array(
                'shop_id' => $shop_id,
                'error' => $response['message'] ?? 'Unknown error',
                'timestamp' => $this->timestamp
            ));
            
            return array(
                'success' => false,
                'message' => 'Failed to register webhook: ' . ($response['message'] ?? 'Unknown error')
            );
        }
    }
    
    /**
     * Get webhook endpoint URL
     *
     * @return string Webhook URL
     */
    public function get_webhook_url() {
        return rest_url($this->namespace . $this->route);
    }
}