<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\WebhookManagement;

use ApolloWeb\WPWooCommercePrintifySync\Services\Import\ProductImportScheduler;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class ProductWebhookHandler
{
    use TimeStampTrait;

    private ProductImportScheduler $scheduler;
    private LoggerInterface $logger;

    public function __construct(
        ProductImportScheduler $scheduler,
        LoggerInterface $logger
    ) {
        $this->scheduler = $scheduler;
        $this->logger = $logger;
    }

    public function handleProductCreated(array $payload): void
    {
        try {
            $this->logger->info('Product webhook received', [
                'event' => 'created',
                'product_id' => $payload['id'],
                'timestamp' => $this->getCurrentTime()
            ]);

            $this->scheduler->scheduleImport(
                [$payload['id']], 
                'webhook_created'
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle product created webhook', [
                'error' => $e->getMessage(),
                'product_id' => $payload['id'],
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }

    public function handleProductUpdated(array $payload): void
    {
        try {
            $this->logger->info('Product webhook received', [
                'event' => 'updated',
                'product_id' => $payload['id'],
                'timestamp' => $this->getCurrentTime()
            ]);

            $this->scheduler->scheduleImport(
                [$payload['id']], 
                'webhook_updated'
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle product updated webhook', [
                'error' => $e->getMessage(),
                'product_id' => $payload['id'],
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }

    public function handleBulkImport(array $productIds): void
    {
        try {
            $this->logger->info('Bulk import initiated', [
                'total_products' => count($productIds),
                'timestamp' => $this->getCurrentTime()
            ]);

            $this->scheduler->scheduleImport(
                $productIds,
                'bulk_import'
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle bulk import', [
                'error' => $e->getMessage(),
                'total_products' => count($productIds),
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }
}