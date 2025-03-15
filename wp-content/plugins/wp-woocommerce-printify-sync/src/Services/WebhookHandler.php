<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class WebhookHandler
{
    private string $currentTime = '2025-03-15 19:50:00';
    private string $currentUser = 'ApolloWeb';
    
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'registerWebhookEndpoint']);
    }

    public function registerWebhookEndpoint(): void
    {
        register_rest_route('wpwps/v1', '/printify', [
            'methods' => 'POST',
            'callback' => [$this, 'handleWebhook'],
            'permission_callback' => [$this, 'validateWebhook']
        ]);
    }

    public function validateWebhook(\WP_REST_Request $request): bool
    {
        $signature = $request->get_header('X-Printify-Signature');
        $webhookSecret = get_option('wpwps_webhook_secret');

        if (!$signature || !$webhookSecret) {
            error_log('[WPWPS] Webhook validation failed: Missing signature or secret');
            return false;
        }

        $payload = $request->get_body();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    public function handleWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $payload = $request->get_json_params();

            // Validate payload
            if (!$this->validatePayload($payload)) {
                throw new \Exception('Invalid payload structure');
            }

            // Queue the import
            $importQueue = new ImportQueue();
            $batchId = $importQueue->queueProduct($payload);

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Product queued for import',
                'batch_id' => $batchId
            ], 200);

        } catch (\Exception $e) {
            error_log(sprintf(
                '[WPWPS] Webhook handling failed: %s - %s',
                $e->getMessage(),
                $this->currentTime
            ));

            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function validatePayload(array $payload): bool
    {
        $required = ['product_id', 'title', 'description', 'variants', 'images'];
        
        foreach ($required as $field) {
            if (!isset($payload[$field])) {
                error_log(sprintf(
                    '[WPWPS] Missing required field: %s - %s',
                    $field,
                    $this->currentTime
                ));
                return false;
            }
        }

        return true;
    }
}