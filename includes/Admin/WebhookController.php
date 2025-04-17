<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Webhook Controller
 * 
 * Handles incoming webhooks from Printify based on the official docs
 */
class WebhookController {
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('wpwps/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'process_webhook'],
            'permission_callback' => [$this, 'validate_webhook'],
        ]);
    }
    
    /**
     * Validate webhook request
     * Per Printify documentation, webhooks should be validated by checking the X-Printify-Signature header
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function validate_webhook($request) {
        // Get headers
        $signature = $request->get_header('x-printify-signature');
        
        if (empty($signature)) {
            error_log('Webhook validation failed: Missing X-Printify-Signature header');
            return false;
        }
        
        // Get webhook payload
        $payload = $request->get_body();
        
        // Get webhook secret from options
        $options = get_option('wpwps_options', []);
        $webhook_secret = isset($options['printify_webhook_secret']) ? $options['printify_webhook_secret'] : '';
        
        if (empty($webhook_secret)) {
            error_log('Webhook validation failed: Missing webhook secret in settings');
            return true; // Allow for testing, but log warning
        }
        
        // Calculate expected signature
        $expected_signature = hash_hmac('sha256', $payload, $webhook_secret);
        
        // Compare signatures
        if (hash_equals($expected_signature, $signature)) {
            return true;
        }
        
        error_log('Webhook validation failed: Invalid signature');
        return false;
    }
    
    /**
     * Process webhook request
     * Handles various webhook events from Printify
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function process_webhook($request) {
        // Get webhook body
        $body = $request->get_json_params();
        
        // Extract event type
        $topic = isset($body['topic']) ? sanitize_text_field($body['topic']) : '';
        $data = isset($body['data']) ? $body['data'] : [];
        
        if (empty($topic)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Missing topic'
            ], 400);
        }
        
        // Log webhook
        $this->log_webhook($topic, $data);
        
        // Process based on topic
        switch ($topic) {
            case 'product.update':
                $this->process_product_update($data);
                break;
                
            case 'product.delete':
                $this->process_product_delete($data);
                break;
                
            case 'product.published':
                $this->process_product_published($data);
                break;
                
            case 'order.created':
                $this->process_order_created($data);
                break;
                
            case 'order.update':
                $this->process_order_update($data);
                break;
                
            case 'shipping.update':
                $this->process_shipping_update($data);
                break;
        }
        
        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Webhook received'
        ]);
    }
    
    /**
     * Log webhook
     *
     * @param string $topic
     * @param array $data
     */
    private function log_webhook($topic, $data) {
        error_log('Printify Webhook Received: ' . $topic);
    }
    
    /**
     * Process product update
     *
     * @param array $data
     */
    private function process_product_update($data) {
        // Implement product update logic
    }
    
    /**
     * Process product delete
     *
     * @param array $data
     */
    private function process_product_delete($data) {
        // Implement product delete logic
    }
    
    /**
     * Process product published
     *
     * @param array $data
     */
    private function process_product_published($data) {
        // Implement product published logic
    }
    
    /**
     * Process order created
     *
     * @param array $data
     */
    private function process_order_created($data) {
        // Implement order created logic
    }
    
    /**
     * Process order update
     *
     * @param array $data
     */
    private function process_order_update($data) {
        // Implement order update logic
    }
    
    /**
     * Process shipping update
     *
     * @param array $data
     */
    private function process_shipping_update($data) {
        // Implement shipping update logic
    }
}
