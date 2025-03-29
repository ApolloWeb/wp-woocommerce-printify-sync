<?php
namespace WPWPS\Services;

/**
 * Handler for Printify webhooks
 */
class WebhookHandler {
    /**
     * @var PrintifyApiService The Printify API service
     */
    private $apiService;
    
    /**
     * @var string The webhook secret used for validation
     */
    private $webhookSecret;
    
    /**
     * @var array Supported webhook topics
     */
    private $supportedTopics = [
        'order:created',
        'order:updated',
        'product:created',
        'product:updated',
        'product:deleted',
        'product:published',
        'product:unpublished',
        'shipment:created',
        'shipment:updated'
    ];
    
    /**
     * WebhookHandler constructor
     */
    public function __construct() {
        $this->apiService = new PrintifyApiService();
        $this->webhookSecret = get_option('wpwps_printify_webhook_secret', '');
        
        // Generate a webhook secret if one doesn't exist
        if (empty($this->webhookSecret)) {
            $this->webhookSecret = $this->generateWebhookSecret();
            update_option('wpwps_printify_webhook_secret', $this->webhookSecret);
        }
    }
    
    /**
     * Register necessary hooks
     */
    public function register(): void {
        add_action('rest_api_init', [$this, 'registerWebhookEndpoints']);
        add_action('wpwps_setup_webhooks', [$this, 'setupWebhooks']);
    }
    
    /**
     * Register REST API endpoints for webhooks
     */
    public function registerWebhookEndpoints(): void {
        register_rest_route('wpwps/v1', '/webhook/printify', [
            'methods' => 'POST',
            'callback' => [$this, 'handleWebhook'],
            'permission_callback' => [$this, 'validateWebhook']
        ]);
    }
    
    /**
     * Validate the incoming webhook request
     * 
     * @param \WP_REST_Request $request The request object
     * @return bool|WP_Error True if valid, WP_Error otherwise
     */
    public function validateWebhook($request) {
        // Skip validation in development if the option is set
        if (defined('WPWPS_DEV_MODE') && WPWPS_DEV_MODE) {
            return true;
        }
        
        // Get the Printify-Signature header
        $signature = $request->get_header('Printify-Signature');
        
        if (empty($signature)) {
            return new \WP_Error(
                'missing_signature',
                'Missing Printify signature header',
                ['status' => 401]
            );
        }
        
        // Get the request body
        $payload = $request->get_body();
        
        // Verify the signature
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        if (!hash_equals($expectedSignature, $signature)) {
            return new \WP_Error(
                'invalid_signature',
                'Invalid Printify signature',
                ['status' => 401]
            );
        }
        
        return true;
    }
    
    /**
     * Handle incoming webhook from Printify
     * 
     * @param \WP_REST_Request $request The request object
     * @return \WP_REST_Response The response
     */
    public function handleWebhook($request) {
        // Get the webhook payload
        $payload = json_decode($request->get_body(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Invalid JSON payload'
            ], 400);
        }
        
        // Log the webhook
        $this->logWebhook($payload);
        
        // Extract the event type
        $topic = isset($payload['topic']) ? sanitize_text_field($payload['topic']) : null;
        
        if (!$topic || !in_array($topic, $this->supportedTopics)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Unsupported webhook topic'
            ], 400);
        }
        
        // Process the webhook based on topic
        $result = $this->processWebhook($topic, $payload);
        
        return new \WP_REST_Response([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'id' => sanitize_text_field($payload['id'] ?? ''),
                'topic' => $topic
            ]
        ], 200);
    }
    
    /**
     * Process webhook based on topic
     * 
     * @param string $topic The webhook topic
     * @param array $payload The webhook payload
     * @return array Result with message
     */
    private function processWebhook(string $topic, array $payload): array {
        switch ($topic) {
            case 'order:created':
                return $this->processOrderCreated($payload);
                
            case 'order:updated':
                return $this->processOrderUpdated($payload);
                
            case 'product:created':
                return $this->processProductCreated($payload);
                
            case 'product:updated':
                return $this->processProductUpdated($payload);
                
            case 'product:deleted':
                return $this->processProductDeleted($payload);
                
            case 'product:published':
                return $this->processProductPublished($payload);
                
            case 'product:unpublished':
                return $this->processProductUnpublished($payload);
                
            case 'shipment:created':
                return $this->processShipmentCreated($payload);
                
            case 'shipment:updated':
                return $this->processShipmentUpdated($payload);
                
            default:
                return [
                    'success' => false,
                    'message' => 'Unsupported webhook topic'
                ];
        }
    }
    
    /**
     * Process order created webhook
     * 
     * @param array $payload The webhook payload
     * @return array Result with message
     */
    private function processOrderCreated(array $payload): array {
        // Extract order data
        $orderId = isset($payload['data']['id']) ? sanitize_text_field($payload['data']['id']) : null;
        
        if (!$orderId) {
            return [
                'success' => false,
                'message' => 'Missing order ID in payload'
            ];
        }
        
        // Schedule order processing
        wp_schedule_single_event(time(), 'wpwps_process_printify_order', [$orderId]);
        
        return [
            'success' => true,
            'message' => "Order {$orderId} scheduled for processing"
        ];
    }
    
    /**
     * Process order updated webhook
     * 
     * @param array $payload The webhook payload
     * @return array Result with message
     */
    private function processOrderUpdated(array $payload): array {
        // Extract order data
        $orderId = isset($payload['data']['id']) ? sanitize_text_field($payload['data']['id']) : null;
        
        if (!$orderId) {
            return [
                'success' => false,
                'message' => 'Missing order ID in payload'
            ];
        }
        
        // Schedule order update
        wp_schedule_single_event(time(), 'wpwps_update_printify_order_status', [$orderId]);
        
        return [
            'success' => true,
            'message' => "Order {$orderId} status update scheduled"
        ];
    }
    
    /**
     * Process product created webhook
     * 
     * @param array $payload The webhook payload
     * @return array Result with message
     */
    private function processProductCreated(array $payload): array {
        // Extract product data
        $productId = isset($payload['data']['id']) ? sanitize_text_field($payload['data']['id']) : null;
        
        if (!$productId) {
            return [
                'success' => false,
                'message' => 'Missing product ID in payload'
            ];
        }
        
        // Schedule product import
        wp_schedule_single_event(time(), 'wpwps_import_printify_product', [$productId]);
        
        return [
            'success' => true,
            'message' => "Product {$productId} scheduled for import"
        ];
    }
    
    /**
     * Process product updated webhook
     * 
     * @param array $payload The webhook payload
     * @return array Result with message
     */
    private function processProductUpdated(array $payload): array {
        // Extract product data
        $productId = isset($payload['data']['id']) ? sanitize_text_field($payload['data']['id']) : null;
        
        if (!$productId) {
            return [
                'success' => false,
                'message' => 'Missing product ID in payload'
            ];
        }
        
        // Schedule product update
        wp_schedule_single_event(time(), 'wpwps_update_printify_product', [$productId]);
        
        return [
            'success' => true,
            'message' => "Product {$productId} update scheduled"
        ];
    }
    
    /**
     * Process product deleted webhook
     * 
     * @param array $payload The webhook payload
     * @return array Result with message
     */
    private function processProductDeleted(array $payload): array {
        // Extract product data
        $productId = isset($payload['data']['id']) ? sanitize_text_field($payload['data']['id']) : null;
        
        if (!$productId) {
            return [
                'success' => false,
                'message' => 'Missing product ID in payload'
            ];
        }
        
        // Schedule product deletion
        wp_schedule_single_event(time(), 'wpwps_delete_printify_product', [$productId]);
        
        return [
            'success' => true,
            'message' => "Product {$productId} deletion scheduled"
        ];
    }
    
    /**
     * Process product published webhook
     * 
     * @param array $payload The webhook payload
     * @return array Result with message
     */
    private function processProductPublished(array $payload): array {
        // Extract product data
        $productId = isset($payload['data']['id']) ? sanitize_text_field($payload['data']['id']) : null;
        
        if (!$productId) {
            return [
                'success' => false,
                'message' => 'Missing product ID in payload'
            ];
        }
        
        // Schedule product publishing
        wp_schedule_single_event(time(), 'wpwps_publish_printify_product', [$productId]);
        
        return [
            'success' => true,
            'message' => "Product {$productId} publishing scheduled"
        ];
    }
    
    /**
     * Process product unpublished webhook
     * 
     * @param array $payload The webhook payload
     * @return array Result with message
     */
    private function processProductUnpublished(array $payload): array {
        // Extract product data
        $productId = isset($payload['data']['id']) ? sanitize_text_field($payload['data']['id']) : null;
        
        if (!$productId) {
            return [
                'success' => false,
                'message' => 'Missing product ID in payload'
            ];
        }
        
        // Schedule product unpublishing
        wp_schedule_single_event(time(), 'wpwps_unpublish_printify_product', [$productId]);
        
        return [
            'success' => true,
            'message' => "Product {$productId} unpublishing scheduled"
        ];
    }
    
    /**
     * Process shipment created webhook
     * 
     * @param array $payload The webhook payload
     * @return array Result with message
     */
    private function processShipmentCreated(array $payload): array {
        // Extract shipment data
        $shipmentId = isset($payload['data']['id']) ? sanitize_text_field($payload['data']['id']) : null;
        $orderId = isset($payload['data']['order_id']) ? sanitize_text_field($payload['data']['order_id']) : null;
        
        if (!$shipmentId || !$orderId) {
            return [
                'success' => false,
                'message' => 'Missing shipment or order ID in payload'
            ];
        }
        
        // Schedule shipment processing
        wp_schedule_single_event(time(), 'wpwps_process_printify_shipment', [$shipmentId, $orderId]);
        
        return [
            'success' => true,
            'message' => "Shipment {$shipmentId} for order {$orderId} scheduled for processing"
        ];
    }
    
    /**
     * Process shipment updated webhook
     * 
     * @param array $payload The webhook payload
     * @return array Result with message
     */
    private function processShipmentUpdated(array $payload): array {
        // Extract shipment data
        $shipmentId = isset($payload['data']['id']) ? sanitize_text_field($payload['data']['id']) : null;
        $orderId = isset($payload['data']['order_id']) ? sanitize_text_field($payload['data']['order_id']) : null;
        
        if (!$shipmentId || !$orderId) {
            return [
                'success' => false,
                'message' => 'Missing shipment or order ID in payload'
            ];
        }
        
        // Schedule shipment update
        wp_schedule_single_event(time(), 'wpwps_update_printify_shipment', [$shipmentId, $orderId]);
        
        return [
            'success' => true,
            'message' => "Shipment {$shipmentId} update for order {$orderId} scheduled"
        ];
    }
    
    /**
     * Set up webhooks for the connected shop
     */
    public function setupWebhooks(): void {
        if (!$this->apiService->isConfigured()) {
            error_log('Cannot set up webhooks: Printify API not configured');
            return;
        }
        
        try {
            // Get existing webhooks
            $existingWebhooks = $this->apiService->getWebhooks();
            
            if ($existingWebhooks === null) {
                throw new \Exception('Failed to retrieve existing webhooks');
            }
            
            // Our webhook endpoint URL
            $webhookUrl = esc_url_raw(rest_url('wpwps/v1/webhook/printify'));
            
            // Check which topics need to be registered
            $registeredTopics = [];
            foreach ($existingWebhooks as $webhook) {
                if (isset($webhook['url']) && $webhook['url'] === $webhookUrl) {
                    $registeredTopics[] = $webhook['topic'];
                }
            }
            
            // Register missing topics
            foreach ($this->supportedTopics as $topic) {
                if (!in_array($topic, $registeredTopics)) {
                    $result = $this->apiService->createWebhook($topic, $webhookUrl);
                    
                    if ($result === null) {
                        error_log("Failed to create webhook for topic: {$topic}");
                    } else {
                        error_log("Successfully registered webhook for topic: {$topic}");
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('Error setting up webhooks: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a secure webhook secret
     */
    private function generateWebhookSecret(): string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Log webhook data for debugging
     * 
     * @param array $payload Webhook payload
     */
    private function logWebhook(array $payload): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Printify Webhook Received: ' . json_encode($payload));
        }
    }
}