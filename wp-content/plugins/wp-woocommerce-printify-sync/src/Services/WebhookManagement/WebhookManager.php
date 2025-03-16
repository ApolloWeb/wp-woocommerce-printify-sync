<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\WebhookManagement;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class WebhookManager
{
    use TimeStampTrait;

    private const REQUIRED_TOPICS = [
        'product.created',
        'product.updated',
        'product.deleted',
        'order.created',
        'order.updated',
        'order.cancelled',
        'shipping.updated',
        'stock.updated'
    ];

    private PrintifyAPIClient $apiClient;
    private ConfigService $config;
    private LoggerInterface $logger;
    private array $existingWebhooks = [];

    public function __construct(
        PrintifyAPIClient $apiClient,
        ConfigService $config,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function setupWebhooks(): void
    {
        try {
            // Load existing webhooks
            $this->loadExistingWebhooks();

            // Create missing webhooks
            foreach (self::REQUIRED_TOPICS as $topic) {
                if (!$this->hasWebhook($topic)) {
                    $this->createWebhook($topic);
                }
            }

            // Clean up unused webhooks
            $this->cleanupUnusedWebhooks();

            $this->logger->info('Webhooks setup completed', [
                'timestamp' => $this->getCurrentTime(),
                'topics' => self::REQUIRED_TOPICS
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to setup webhooks', [
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }

    private function loadExistingWebhooks(): void
    {
        $response = $this->apiClient->get('webhooks');
        $this->existingWebhooks = $response['data'] ?? [];
    }

    private function hasWebhook(string $topic): bool
    {
        $webhookUrl = $this->getWebhookUrl();
        
        foreach ($this->existingWebhooks as $webhook) {
            if ($webhook['topic'] === $topic && $webhook['url'] === $webhookUrl) {
                return true;
            }
        }
        
        return false;
    }

    private function createWebhook(string $topic): void
    {
        try {
            $response = $this->apiClient->post('webhooks', [
                'topic' => $topic,
                'url' => $this->getWebhookUrl(),
                'secret' => $this->generateWebhookSecret(),
                'enabled' => true,
                'filters' => $this->getTopicFilters($topic)
            ]);

            if (isset($response['id'])) {
                $this->logger->info('Webhook created', [
                    'topic' => $topic,
                    'webhook_id' => $response['id'],
                    'timestamp' => $this->getCurrentTime()
                ]);

                // Store webhook secret
                $this->storeWebhookSecret($response['id'], $response['secret']);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to create webhook', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }

    private function cleanupUnusedWebhooks(): void
    {
        $webhookUrl = $this->getWebhookUrl();
        
        foreach ($this->existingWebhooks as $webhook) {
            if ($webhook['url'] === $webhookUrl && 
                !in_array($webhook['topic'], self::REQUIRED_TOPICS)) {
                $this->deleteWebhook($webhook['id']);
            }
        }
    }

    private function deleteWebhook(string $webhookId): void
    {
        try {
            $this->apiClient->delete("webhooks/{$webhookId}");
            $this->logger->info('Webhook deleted', [
                'webhook_id' => $webhookId,
                'timestamp' => $this->getCurrentTime()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete webhook', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
        }
    }

    private function getWebhookUrl(): string
    {
        return add_query_arg([
            'action' => 'printify_webhook',
            'shop' => $this->config->get('shop_id')
        ], get_rest_url(null, 'printify/v1/webhook'));
    }

    private function generateWebhookSecret(): string
    {
        return wp_generate_password(32, true, true);
    }

    private function storeWebhookSecret(string $webhookId, string $secret): void
    {
        update_option(
            'wpwps_webhook_secret_' . $webhookId,
            $this->encryptSecret($secret)
        );
    }

    private function encryptSecret(string $secret): string
    {
        // Use WordPress' password hashing for storing the secret
        return wp_hash_password($secret);
    }

    private function getTopicFilters(string $topic): array
    {
        $filters = [];

        switch ($topic) {
            case 'product.created':
            case 'product.updated':
                $filters['shop_id'] = $this->config->get('shop_id');
                break;

            case 'order.created':
            case 'order.updated':
                $filters['status'] = ['pending', 'processing', 'shipped'];
                break;

            case 'shipping.updated':
                $filters['carrier'] = ['all'];
                break;

            case 'stock.updated':
                $filters['threshold'] = 5; // Alert when stock below 5
                break;
        }

        return $filters;
    }

    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        foreach ($this->existingWebhooks as $webhook) {
            $secret = get_option('wpwps_webhook_secret_' . $webhook['id']);
            if ($secret && hash_equals(
                hash_hmac('sha256', $payload, $secret),
                $signature
            )) {
                return true;
            }
        }

        return false;
    }
}