<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Webhook Controller for handling Printify webhook requests
 */
class WebhookController {
    /**
     * @var WebhookHandlerInterface
     */
    private $webhook_handler;
    
    /**
     * Constructor
     *
     * @param WebhookHandlerInterface $webhook_handler
     */
    public function __construct(WebhookHandlerInterface $webhook_handler) {
        $this->webhook_handler = $webhook_handler;
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('wpwps/v1', '/webhook', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => [$this, 'check_permission']
        ]);
    }
    
    /**
     * Handle incoming webhook requests
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function handle_webhook(WP_REST_Request $request) {
        $headers = $request->get_headers();
        $body = $request->get_json_params();
        
        // Extract event type from headers or payload
        $event_type = '';
        
        if (!empty($headers['x-printify-event']) && is_array($headers['x-printify-event'])) {
            $event_type = $headers['x-printify-event'][0];
        } elseif (!empty($body['event'])) {
            $event_type = $body['event'];
        }
        
        if (empty($event_type)) {
            return new WP_Error('invalid_webhook', 'Invalid webhook event type', ['status' => 400]);
        }
        
        // Process webhook based on event type
        $result = $this->webhook_handler->process_event($event_type, $body);
        
        if ($result) {
            return new WP_REST_Response(['success' => true], 200);
        } else {
            return new WP_Error('processing_failed', 'Failed to process webhook', ['status' => 500]);
        }
    }
    
    /**
     * Check if the request has valid permissions
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_permission(WP_REST_Request $request) {
        // Validate request is from Printify
        // For Printify webhooks, there's usually no authentication required
        // as they're sent to a unique, non-guessable URL.
        // Add additional validation as needed (IP allowlisting, etc.)
        
        return true;
    }
}
