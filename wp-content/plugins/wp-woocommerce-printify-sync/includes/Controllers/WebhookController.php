<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Controllers;

use ApolloWeb\WPWooCommercePrintifySync\Services\OrderSyncService;
use ApolloWeb\WPWooCommercePrintifySync\Services\ProductSyncService;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;

/**
 * Handles Printify webhooks
 */
class WebhookController {
    /**
     * @var OrderSyncService
     */
    private $order_sync;
    
    /**
     * @var ProductSyncService
     */
    private $product_sync;
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var string Webhook secret for verification
     */
    private $webhook_secret;
    
    /**
     * Constructor
     */
    public function __construct(OrderSyncService $order_sync, ProductSyncService $product_sync, Logger $logger) {
        $this->order_sync = $order_sync;
        $this->product_sync = $product_sync;
        $this->logger = $logger;
        $this->webhook_secret = get_option('wpwps_webhook_secret', '');
        
        // Register webhook endpoint
        add_action('init', [$this, 'registerWebhookEndpoint']);
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_generate_webhook_secret', [$this, 'generateWebhookSecret']);
        add_action('wp_ajax_wpwps_test_webhook', [$this, 'testWebhook']);
    }
    
    /**
     * Register webhook endpoint
     */
    public function registerWebhookEndpoint(): void {
        add_rewrite_rule(
            'wp-json/wpwps/v1/webhook/?$',
            'index.php?wpwps_webhook=1',
            'top'
        );
        
        add_rewrite_tag('%wpwps_webhook%', '([0-9]+)');
        
        add_action('template_redirect', [$this, 'handleWebhook']);
    }
    
    /**
     * Handle webhook request
     */
    public function handleWebhook(): void {
        if (!get_query_var('wpwps_webhook')) {
            return;
        }
        
        // Verify request
        if (!$this->verifyWebhookRequest()) {
            $this->logger->log('Webhook verification failed', 'error');
            status_header(403);
            echo json_encode(['error' => 'Verification failed']);
            exit;
        }
        
        // Get JSON body
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if (empty($data) || !isset($data['type'])) {
            $this->logger->log('Invalid webhook data received', 'error');
            status_header(400);
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }
        
        // Log webhook
        $this->logger->log('Webhook received: ' . $data['type'], 'info');
        
        // Process webhook
        try {
            $response = $this->processWebhook($data);
            echo json_encode($response);
        } catch (\Exception $e) {
            $this->logger->log('Error processing webhook: ' . $e->getMessage(), 'error');
            status_header(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        
        exit;
    }
    
    /**
     * Process webhook based on type
     * 
     * @param array $data Webhook data
     * @return array Response data
     */
    private function processWebhook(array $data): array {
        switch ($data['type']) {
            case 'order:created':
            case 'order:updated':
                return $this->processOrderWebhook($data);
                
            case 'product:created':
            case 'product:updated':
                return $this->processProductWebhook($data);
                
            case 'shipment:created':
                return $this->processShipmentWebhook($data);
                
            default:
                $this->logger->log('Unknown webhook type: ' . $data['type'], 'warning');
                return ['status' => 'ignored', 'message' => 'Unknown webhook type'];
        }
    }
    
    /**
     * Process order webhook
     * 
     * @param array $data Webhook data
     * @return array Response data
     */
    private function processOrderWebhook(array $data): array {
        if (empty($data['order_id'])) {
            return ['status' => 'error', 'message' => 'No order ID provided'];
        }
        
        // Schedule the order import with a slight delay to ensure it's in Printify's system
        as_schedule_single_action(
            time() + 30, // 30 second delay
            'wpwps_import_order',
            ['printify_order_id' => $data['order_id']]
        );
        
        return [
            'status' => 'scheduled',
            'message' => 'Order import scheduled',
            'order_id' => $data['order_id']
        ];
    }
    
    /**
     * Process product webhook
     * 
     * @param array $data Webhook data
     * @return array Response data
     */
    private function processProductWebhook(array $data): array {
        if (empty($data['product_id'])) {
            return ['status' => 'error', 'message' => 'No product ID provided'];
        }
        
        // Schedule the product import with a slight delay
        as_schedule_single_action(
            time() + 30, // 30 second delay
            'wpwps_sync_product',
            ['printify_id' => $data['product_id'], 'create_if_not_exists' => true]
        );
        
        return [
            'status' => 'scheduled',
            'message' => 'Product sync scheduled',
            'product_id' => $data['product_id']
        ];
    }
    
    /**
     * Process shipment webhook
     * 
     * @param array $data Webhook data
     * @return array Response data
     */
    private function processShipmentWebhook(array $data): array {
        if (empty($data['order_id'])) {
            return ['status' => 'error', 'message' => 'No order ID provided'];
        }
        
        // Schedule the shipment processing
        as_schedule_single_action(
            time() + 15, // 15 second delay
            'wpwps_process_shipment',
            ['printify_order_id' => $data['order_id']]
        );
        
        return [
            'status' => 'scheduled',
            'message' => 'Shipment processing scheduled',
            'order_id' => $data['order_id']
        ];
    }
    
    /**
     * Verify webhook request
     * 
     * @return bool Is valid
     */
    private function verifyWebhookRequest(): bool {
        // Skip verification if no secret is set (not recommended for production)
        if (empty($this->webhook_secret)) {
            return true;
        }
        
        // Get signature from header
        $signature = $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] ?? '';
        
        if (empty($signature)) {
            return false;
        }
        
        // Get request body
        $body = file_get_contents('php://input');
        
        // Compute expected signature
        $expected = hash_hmac('sha256', $body, $this->webhook_secret);
        
        // Compare signatures
        return hash_equals($expected, $signature);
    }
    
    /**
     * Generate webhook secret
     */
    public function generateWebhookSecret(): void {
        check_ajax_referer('wpps_admin');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
        }
        
        $secret = wp_generate_password(32, true, true);
        update_option('wpwps_webhook_secret', $secret);
        
        $this->logger->log('Webhook secret regenerated', 'info');
        
        wp_send_json_success([
            'message' => __('Webhook secret regenerated', 'wp-woocommerce-printify-sync'),
            'secret' => $secret
        ]);
    }
    
    /**
     * Test webhook
     */
    public function testWebhook(): void {
        check_ajax_referer('wpps_admin');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
        }
        
        $webhook_url = home_url('wpwps-webhook');
        
        // Send a simple test request to the webhook endpoint
        $response = wp_remote_post($webhook_url, [
            'body' => json_encode([
                'event' => 'test',
                'data' => [
                    'message' => 'This is a test webhook'
                ]
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Printify-Signature' => hash_hmac('sha256', '{"event":"test","data":{"message":"This is a test webhook"}}', get_option('wpwps_webhook_secret'))
            ]
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code >= 200 && $status_code < 300) {
            wp_send_json_success([
                'message' => __('Webhook endpoint is working', 'wp-woocommerce-printify-sync'),
                'response' => $body
            ]);
        } else {
            wp_send_json_error([
                'message' => sprintf(__('Webhook test failed with status code %d', 'wp-woocommerce-printify-sync'), $status_code),
                'response' => $body
            ]);
        }
    }
    
    /**
     * Get webhook URL for display in settings
     */
    public static function getWebhookUrl(): string {
        return home_url('wpwps-webhook');
    }
    
    /**
     * Get masked webhook secret for display in settings
     */
    public static function getMaskedWebhookSecret(): string {
        $secret = get_option('wpwps_webhook_secret');
        
        if (empty($secret)) {
            return '';
        }
        
        $length = strlen($secret);
        $visible_chars = 4;
        
        if ($length <= $visible_chars * 2) {
            return str_repeat('*', $length);
        }
        
        return substr($secret, 0, $visible_chars) . str_repeat('*', $length - $visible_chars * 2) . substr($secret, -$visible_chars);
    }
}
