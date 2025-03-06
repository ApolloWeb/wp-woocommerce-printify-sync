<?php
/**
 * Printify Webhook Handler - Enhanced to handle additional data
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API\Webhook
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API\Webhook;

use ApolloWeb\WPWooCommercePrintifySync\API\Printify\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderManager;
use ApolloWeb\WPWooCommercePrintifySync\Products\ProductManager;
use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

class PrintifyWebhookHandler implements WebhookHandlerInterface {
    private static $instance = null;
    private $api_client;
    private $order_manager;
    private $product_manager;
    private $webhook_secret;
    private $timestamp = '2025-03-05 19:33:51';
    private $user = 'ApolloWeb';
    
    /**
     * Get single instance
     */
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
        $this->api_client = PrintifyApiClient::getInstance();
        $this->order_manager = OrderManager::getInstance();
        $this->product_manager = ProductManager::getInstance();
        $this->webhook_secret = get_option('wpwprintifysync_webhook_secret', '');
    }
    
    /**
     * Process incoming webhook request
     *
     * @param string $payload JSON payload from request body
     * @param array $headers Request headers
     * @param array $additional_data Additional data passed with the request
     * @return array Response data
     */
    public function processWebhook(string $payload, array $headers, array $additional_data = []): array {
        // Log webhook receipt with additional data
        $log_context = [
            'timestamp' => $this->timestamp,
            'user' => $this->user
        ];
        
        if (!empty($additional_data)) {
            $log_context['additional_data'] = $additional_data;
        }
        
        Logger::getInstance()->info('Webhook received', $log_context);
        
        // Validate webhook signature if secret is set
        if (!empty($this->webhook_secret)) {
            $signature = isset($headers['X-Printify-Signature']) ? $headers['X-Printify-Signature'] : '';
            if (!$this->verifySignature($payload, $signature)) {
                Logger::getInstance()->error('Invalid webhook signature', [
                    'timestamp' => $this->timestamp
                ]);
                return [
                    'success' => false,
                    'message' => 'Invalid signature',
                    'code' => 401
                ];
            }
        }
        
        // Parse payload
        $event_data = json_decode($payload, true);
        if (empty($event_data) || !isset($event_data['type'])) {
            Logger::getInstance()->error('Invalid webhook payload', [
                'payload' => substr($payload, 0, 255)
            ]);
            return [
                'success' => false,
                'message' => 'Invalid payload',
                'code' => 400
            ];
        }
        
        // Merge additional data into event data if provided
        if (!empty($additional_data)) {
            $event_data = array_merge($event_data, ['additional_data' => $additional_data]);
        }
        
        // Process based on event type
        $event_type = $event_data['type'];
        
        switch ($event_type) {
            case 'product.published':
                return $this->handleProductPublished($event_data);
                
            case 'product.unpublished':
                return $this->handleProductUnpublished($event_data);
                
            case 'product.updated':
                return $this->handleProductUpdated($event_data);
                
            case 'order.created':
                return $this->handleOrderCreated($event_data);
                
            case 'order.updated':
                return $this->handleOrderUpdated($event_data);
                
            case 'order.fulfilled':
                return $this->handleOrderFulfilled($event_data);
                
            case 'shipping.update':
                return $this->handleShippingUpdate($event_data);
                
            default:
                Logger::getInstance()->notice('Unhandled webhook event type', [
                    'type' => $event_type
                ]);
                return [
                    'success' => true,
                    'message' => 'Event ignored',
                    'code' => 200
                ];
        }
    }
    
    /**
     * Register webhook with Printify API
     *
     * @return array Response data
     */
    public function registerWebhook(): array {
        // Generate webhook secret if not exists
        if (empty($this->webhook_secret)) {
            $this->webhook_secret = wp_generate_password(32, false);
            update_option('wpwprintifysync_webhook_secret', $this->webhook_secret);
        }
        
        // Get webhook endpoint URL
        $webhook_url = WebhookReceiver::getInstance()->getWebhookUrl();
        
        // Check for existing webhook
        $webhook_id = get_option('wpwprintifysync_webhook_id');
        
        // Define events we want to listen for
        $events = [
            'product.published',
            'product.unpublished',
            'product.updated',
            'order.created',
            'order.updated',
            'order.fulfilled',
            'shipping.update',
            // Add additional event types as needed
        ];
        
        if ($webhook_id) {
            // Update existing webhook
            $response = $this->api_client->request("webhooks/{$webhook_id}.json", [
                'method' => 'PUT',
                'body' => [
                    'url' => $webhook_url,
                    'secret' => $this->webhook_secret,
                    'events' => $events,
                    'enabled' => true,
                    'send_provider_id' => true // Request provider_id be included
                ]
            ]);
        } else {
            // Create new webhook
            $response = $this->api_client->request('webhooks.json', [
                'method' => 'POST',
                'body' => [
                    'url' => $webhook_url,
                    'secret' => $this->webhook_secret,
                    'events' => $events,
                    'enabled' => true,
                    'send_provider_id' => true // Request provider_id be included
                ]
            ]);
            
            // Save webhook ID if created successfully
            if ($response['success'] && isset($response['body']['id'])) {
                update_option('wpwprintifysync_webhook_id', $response['body']['id']);
            }
        }
        
        if ($response['success']) {
            // Update webhook status transient
            set_transient('wpwprintifysync_webhook_status', 'active', HOUR_IN_SECONDS);
            
            Logger::getInstance()->info('Webhook registered successfully with extended data support', [
                'url' => $webhook_url,
                'events' => $events
            ]);
        } else {
            Logger::getInstance()->error('Failed to register webhook', [
                'error' => $response['message'] ?? 'Unknown error'
            ]);
        }
        
        return $response;
    }
    
    /**
     * Verify webhook signature
     *
     * @param string $payload Request body
     * @param string $signature Webhook signature from header
     * @return bool Verification result
     */
    public function verifySignature(string $payload, string $signature): bool {
        if (empty($this->webhook_secret) || empty($signature)) {
            return false;
        }
        
        $expected_signature = hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Handle product published event
     *
     * @param array $event_data Event data
     * @return array Response data
     */
    private function handleProductPublished(array $event_data): array {
        if (!isset($event_data['data']['id']) || !isset($event_data['data']['shop_id'])) {
            return [
                'success' => false,
                'message' => 'Missing product data',
                'code' => 400
            ];
        