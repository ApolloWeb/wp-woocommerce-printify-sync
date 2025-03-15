<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Controllers;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\WebhookValidatorInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\QueueHandlerInterface;

class WebhookController
{
    private string $currentTime = '2025-03-15 19:52:43';
    private string $currentUser = 'ApolloWeb';
    private WebhookValidatorInterface $validator;
    private QueueHandlerInterface $queueHandler;

    public function __construct(
        WebhookValidatorInterface $validator,
        QueueHandlerInterface $queueHandler
    ) {
        $this->validator = $validator;
        $this->queueHandler = $queueHandler;
        
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route('wpwps/v1', '/webhook', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'handleWebhook'],
                'permission_callback' => [$this, 'validateWebhook'],
            ]
        ]);
    }

    public function validateWebhook(\WP_REST_Request $request): bool
    {
        return $this->validator->validate(
            $request->get_body(),
            $request->get_header('X-Printify-Signature')
        );
    }

    public function handleWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload = $request->get_json_params();

        try {
            if (!isset($payload['type'], $payload['data'])) {
                throw new \Exception('Invalid webhook payload');
            }

            if (!in_array($payload['type'], ['product.created', 'product.updated'])) {
                throw new \Exception('Unsupported webhook type');
            }

            $batchId = $this->queueHandler->queue($payload['data']);

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Product queued for import',
                'batch_id' => $batchId
            ], 200);

        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}