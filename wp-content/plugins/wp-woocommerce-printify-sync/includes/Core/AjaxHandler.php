<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Core AJAX request handler
 */
class AjaxHandler {
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var string Nonce action name
     */
    private $nonce_action = 'wpps_admin';
    
    /**
     * Constructor
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Initialize the handler
     */
    public function init(): void {
        // Register batch handler
        add_action('wp_ajax_wpps_batch', [$this, 'handleBatchRequest']);
        
        // Global AJAX error handling
        add_action('wp_ajax_nopriv_wpps_', [$this, 'handleUnauthorized']);
    }
    
    /**
     * Handle batch requests
     */
    public function handleBatchRequest(): void {
        $this->verifyNonce();
        
        if (!current_user_can('manage_woocommerce')) {
            $this->sendErrorResponse('Permission denied', 403);
        }
        
        $requests = json_decode(stripslashes($_POST['requests'] ?? '[]'), true);
        if (!is_array($requests)) {
            $this->sendErrorResponse('Invalid batch request format');
        }
        
        $responses = [];
        foreach ($requests as $index => $request) {
            $action = $request['action'] ?? '';
            $data = $request['data'] ?? [];
            
            try {
                // Route to appropriate handler
                $handler = $this->getActionHandler($action);
                if (!$handler) {
                    throw new \Exception("Unknown action: {$action}");
                }
                
                $response = call_user_func($handler, $data);
                $responses[$index] = [
                    'success' => true,
                    'data' => $response
                ];
            } catch (\Exception $e) {
                $this->logger->log("Batch action '{$action}' failed: " . $e->getMessage(), 'error');
                $responses[$index] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        wp_send_json(['responses' => $responses]);
    }
    
    /**
     * Handle unauthorized requests
     */
    public function handleUnauthorized(): void {
        $this->sendErrorResponse('You must be logged in to perform this action', 401);
    }
    
    /**
     * Get handler for a specific action
     */
    private function getActionHandler(string $action) {
        $handlers = apply_filters('wpps_ajax_handlers', [
            // Core handlers
            'test_connection' => [$this, 'testConnection'],
            // Add more handlers here
        ]);
        
        return $handlers[$action] ?? null;
    }
    
    /**
     * Verify nonce
     */
    protected function verifyNonce(): void {
        $nonce = $_REQUEST['_ajax_nonce'] ?? '';
        if (!wp_verify_nonce($nonce, $this->nonce_action)) {
            $this->sendErrorResponse('Invalid security token', 403);
        }
    }
    
    /**
     * Standard error response
     */
    protected function sendErrorResponse(string $message, int $status_code = 400): void {
        status_header($status_code);
        wp_send_json_error(['message' => $message]);
        exit;
    }
    
    /**
     * Example test connection handler
     */
    public function testConnection($data): array {
        // Implementation would go here
        return ['status' => 'connected'];
    }
}
