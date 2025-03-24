<?php
/**
 * Webhook Handler for Printify API
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;
use ApolloWeb\WPWooCommercePrintifySync\Services\Container;

/**
 * WebhookHandler class.
 */
class WebhookHandler {
    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Service container
     *
     * @var Container
     */
    private Container $container;

    /**
     * Constructor
     *
     * @param LoggerService $logger    Logger service.
     * @param Container     $container Service container.
     */
    public function __construct(LoggerService $logger, Container $container) {
        $this->logger = $logger;
        $this->container = $container;
        
        // Register the webhook endpoint
        add_action('rest_api_init', [$this, 'registerWebhookEndpoint']);
        
        // Add rewrite rule for the endpoint
        add_action('init', [$this, 'addRewriteRules']);
        
        // Handle webhook request
        add_action('parse_request', [$this, 'parseWebhookRequest']);
    }

    /**
     * Register the webhook endpoint
     *
     * @return void
     */
    public function registerWebhookEndpoint(): void {
        register_rest_route('wpwps/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handleWebhook'],
            'permission_callback' => '__return_true', // We'll validate via webhook secret
        ]);
    }

    /**
     * Add rewrite rules for the webhook endpoint
     *
     * @return void
     */
    public function addRewriteRules(): void {
        add_rewrite_rule(
            '^wpwps/webhook/?$',
            'index.php?rest_route=/wpwps/v1/webhook',
            'top'
        );
        
        add_rewrite_endpoint('wpwps-webhook', EP_ROOT);
    }

    /**
     * Parse webhook request
     *
     * @param \WP $wp WordPress request object.
     * @return void
     */
    public function parseWebhookRequest(\WP $wp): void {
        if (empty($wp->query_vars['wpwps-webhook'])) {
            return;
        }
        
        // This is our custom endpoint, handle it directly
        $this->handleWebhookLegacy();
        exit;
    }

    /**
     * Handle webhook request (legacy method - direct)
     *
     * @return void
     */
    private function handleWebhookLegacy(): void {
        $this->logger->info('Received webhook request (legacy endpoint)');
        
        // Get the request body
        $request_body = file_get_contents('php://input');
        
        if (empty($request_body)) {
            $this->logger->error('Empty webhook request body');
            status_header(400);
            echo json_encode(['error' => 'Empty request body']);
            exit;
        }
        
        // Parse the request body
        $payload = json_decode($request_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Invalid JSON in webhook request', ['error' => json_last_error_msg()]);
            status_header(400);
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }
        
        // Verify the webhook
        if (!$this->verifyWebhook($payload)) {
            $this->logger->error('Webhook verification failed');
            status_header(401);
            echo json_encode(['error' => 'Webhook verification failed']);
            exit;
        }
        
        // Process the webhook
        $result = $this->processWebhook($payload);
        
        if ($result['success']) {
            status_header(200);
            echo json_encode(['message' => $result['message']]);
        } else {
            status_header(422);
            echo json_encode(['error' => $result['message']]);
        }
        
        exit;
    }

    /**
     * Handle webhook request (REST API method)
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function handleWebhook(\WP_REST_Request $request): \WP_REST_Response {
        $this->logger->info('Received webhook request');
        
        // Get the request body
        $payload = $request->get_json_params();
        
        if (empty($payload)) {
            $this->logger->error('Empty webhook request body');
            return new \WP_REST_Response(['error' => 'Empty request body'], 400);
        }
        
        // Verify the webhook
        if (!$this->verifyWebhook($payload)) {
            $this->logger->error('Webhook verification failed');
            return new \WP_REST_Response(['error' => 'Webhook verification failed'], 401);
        }
        
        // Process the webhook
        $result = $this->processWebhook($payload);
        
        if ($result['success']) {
            return new \WP_REST_Response(['message' => $result['message']], 200);
        } else {
            return new \WP_REST_Response(['error' => $result['message']], 422);
        }
    }

    /**
     * Verify webhook authenticity
     *
     * @param array $payload Webhook payload.
     * @return bool
     */
    private function verifyWebhook(array $payload): bool {
        // Get the webhook secret
        $webhook_secret = get_option('wpwps_webhook_secret', '');
        
        if (empty($webhook_secret)) {
            $this->logger->warning('Webhook secret not configured');
            return false;
        }
        
        // Get the signature from the headers
        $signature = isset($_SERVER['HTTP_X_PRINTIFY_SIGNATURE']) ? sanitize_text_field($_SERVER['HTTP_X_PRINTIFY_SIGNATURE']) : '';
        
        if (empty($signature)) {
            $this->logger->warning('Webhook signature missing from headers');
            return false;
        }
        
        // Convert payload to JSON string
        $payload_json = json_encode($payload);
        
        // Calculate expected signature
        $expected_signature = hash_hmac('sha256', $payload_json, $webhook_secret);
        
        // Verify signature
        if (!hash_equals($expected_signature, $signature)) {
            $this->logger->warning('Webhook signature verification failed', [
                'expected' => $expected_signature,
                'received' => $signature,
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * Process webhook payload
     *
     * @param array $payload Webhook payload.
     * @return array Success status and message.
     */
    private function processWebhook(array $payload): array {
        // Check if we have the required fields
        if (!isset($payload['type']) || !isset($payload['data'])) {
            $this->logger->error('Invalid webhook payload structure', ['payload' => $payload]);
            return [
                'success' => false,
                'message' => 'Invalid webhook payload structure',
            ];
        }

        $event_type = $payload['type'];
        $event_data = $payload['data'];

        $this->logger->info('Processing webhook', [
            'type' => $event_type,
            'data' => $event_data,
        ]);

        // Dispatch to appropriate handler based on event type
        switch ($event_type) {
            // Product events
            case 'product.created':
            case 'product.updated': 
            case 'product.deleted':
                return $this->handleProductEvent($event_type, $event_data);

            // Order events    
            case 'order.created':
            case 'order.updated':
            case 'order.draft':
            case 'order.processing':
            case 'order.pending':
            case 'order.on_hold':
            case 'order.fulfilled':
            case 'order.canceled':
            case 'order.refunded':
            case 'order.failed':
            case 'order.return_requested':
            case 'order.return_approved':
            case 'order.return_rejected':
            case 'order.refund_requested':
            case 'order.refund_approved': 
            case 'order.refund_rejected':
            case 'order.replacement_created':
                return $this->handleOrderEvent($event_type, $event_data);

            // Shipping events
            case 'shipping.created':
            case 'shipping.pending':
            case 'shipping.in_transit':
            case 'shipping.delivered':
            case 'shipping.failed':
            case 'shipping.exception':
                return $this->handleShipmentEvent($event_type, $event_data);

            // Print provider events
            case 'print-provider.order.received':
            case 'print-provider.order.processing':
            case 'print-provider.order.printed':
            case 'print-provider.order.shipped':
            case 'print-provider.order.delayed':
            case 'print-provider.order.canceled':
            case 'print-provider.order.error':
                return $this->handlePrintProviderEvent($event_type, $event_data);

            // Handle unknown events
            default:
                $this->logger->warning('Unhandled webhook event type', ['type' => $event_type]);
                return [
                    'success' => true, 
                    'message' => 'Unhandled event type: ' . $event_type,
                ];
        }
    }

    /**
     * Handle product-related events
     *
     * @param string $event_type Event type.
     * @param array  $event_data Event data.
     * @return array Success status and message.
     */
    private function handleProductEvent(string $event_type, array $event_data): array {
        $this->logger->info('Handling product event', [
            'type' => $event_type,
            'product_id' => $event_data['id'] ?? 'unknown',
        ]);
        
        // Get ProductManager instance
        $product_manager = $this->container->get('product_manager');
        
        // Dispatch event to ProductManager
        do_action('wpwps_webhook_product_update', $event_type, $event_data);
        
        return [
            'success' => true,
            'message' => 'Product event processed successfully',
        ];
    }

    /**
     * Handle order-related events
     *
     * @param string $event_type Event type.
     * @param array  $event_data Event data.
     * @return array Success status and message.
     */
    private function handleOrderEvent(string $event_type, array $event_data): array {
        $this->logger->info('Handling order event', [
            'type' => $event_type,
            'order_id' => $event_data['id'] ?? 'unknown',
        ]);
        
        // Dispatch event for order processing
        do_action('wpwps_webhook_order_update', $event_type, $event_data);
        
        return [
            'success' => true,
            'message' => 'Order event processed successfully',
        ];
    }

    /**
     * Handle shipment-related events
     *
     * @param string $event_type Event type.
     * @param array  $event_data Event data.
     * @return array Success status and message.
     */
    private function handleShipmentEvent(string $event_type, array $event_data): array {
        $this->logger->info('Handling shipment event', [
            'type' => $event_type,
            'shipment_id' => $event_data['id'] ?? 'unknown',
        ]);
        
        // Dispatch event for shipment processing
        do_action('wpwps_webhook_shipment_update', $event_type, $event_data);
        
        return [
            'success' => true,
            'message' => 'Shipment event processed successfully',
        ];
    }

    /**
     * Handle print provider events
     * 
     * @param string $event_type Event type
     * @param array $event_data Event data
     * @return array Success status and message
     */
    private function handlePrintProviderEvent(string $event_type, array $event_data): array {
        $this->logger->info('Handling print provider event', [
            'type' => $event_type,
            'order_id' => $event_data['order_id'] ?? 'unknown',
            'status' => $event_data['status'] ?? 'unknown'
        ]);

        // Dispatch event for print provider processing
        do_action('wpwps_webhook_print_provider_update', $event_type, $event_data);

        return [
            'success' => true,
            'message' => 'Print provider event processed successfully'
        ];
    }

    /**
     * Generate a random webhook secret
     *
     * @return string
     */
    public static function generateWebhookSecret(): string {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get the webhook URL
     *
     * @return string
     */
    public static function getWebhookUrl(): string {
        return rest_url('wpwps/v1/webhook');
    }
}