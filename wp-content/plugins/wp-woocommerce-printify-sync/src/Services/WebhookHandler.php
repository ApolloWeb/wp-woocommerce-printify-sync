<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Context\SyncContext;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\RateLimiter;
use ApolloWeb\WPWooCommercePrintifySync\Queue\QueueManager;

class WebhookHandler
{
    private const WEBHOOK_SECRET = 'your_webhook_secret';
    private const MAX_REQUESTS_PER_MINUTE = 60;

    private LoggerInterface $logger;
    private SyncContext $context;
    private QueueManager $queueManager;
    private RateLimiter $rateLimiter;

    public function __construct(
        LoggerInterface $logger,
        SyncContext $context,
        QueueManager $queueManager,
        RateLimiter $rateLimiter
    ) {
        $this->logger = $logger;
        $this->context = $context;
        $this->queueManager = $queueManager;
        $this->rateLimiter = $rateLimiter;
    }

    public function handleRequest(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            if (!$this->validateSignature($request)) {
                return new \WP_REST_Response(['error' => 'Invalid signature'], 401);
            }

            if (!$this->rateLimiter->allowRequest('webhook', self::MAX_REQUESTS_PER_MINUTE)) {
                return new \WP_REST_Response(['error' => 'Too many requests'], 429);
            }

            $payload = $request->get_json_params();
            
            if (!$this->validatePayload($payload)) {
                return new \WP_REST_Response(['error' => 'Invalid payload'], 400);
            }

            $syncId = $this->queueManager->scheduleWebhookUpdate(
                $payload['product_id'],
                $payload['shop_id'],
                $payload['event']
            );

            $this->logger->info('Webhook received', [
                'sync_id' => $syncId,
                'event' => $payload['event'],
                'product_id' => $payload['product_id']
            ]);

            return new \WP_REST_Response([
                'status' => 'scheduled',
                'sync_id' => $syncId
            ], 202);

        } catch (\Exception $e) {
            $this->logger->error('Webhook handling failed', [
                'error' => $e->getMessage(),
                'payload' => $payload ?? null
            ]);
            return new \WP_REST_Response(['error' => $e->getMessage()], 500);
        }
    }

    private function validateSignature(\WP_REST_Request $request): bool
    {
        $signature = $request->get_header('X-Printify-Signature');
        if (!$signature) {
            return false;
        }

        $payload = $request->get_body();
        $expectedSignature = hash_hmac('sha256', $payload, self::WEBHOOK_SECRET);

        return hash_equals($expectedSignature, $signature);
    }

    private function validatePayload(array $payload): bool
    {
        $required = ['product_id', 'shop_id', 'event'];
        foreach ($required as $field) {
            if (!isset($payload[$field])) {
                return false;
            }
        }
        return true;
    }
}