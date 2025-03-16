<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\PrintifyClientInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class ProductImportManager
{
    private PrintifyClientInterface $printifyClient;
    private ProductSyncService $syncService;
    private LoggerInterface $logger;
    private QueueManager $queueManager;

    public function __construct(
        PrintifyClientInterface $printifyClient,
        ProductSyncService $syncService,
        LoggerInterface $logger,
        QueueManager $queueManager
    ) {
        $this->printifyClient = $printifyClient;
        $this->syncService = $syncService;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
    }

    public function initiateImport(string $shopId): void
    {
        try {
            // Get all products from Printify
            $printifyProducts = $this->printifyClient->getAllProducts($shopId);
            
            // Schedule import in chunks
            $this->queueManager->scheduleProductImport(
                array_column($printifyProducts, 'id')
            );

            $this->logger->info('Product import initiated', [
                'total_products' => count($printifyProducts),
                'shop_id' => $shopId
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to initiate import', [
                'error' => $e->getMessage(),
                'shop_id' => $shopId
            ]);
            throw $e;
        }
    }

    public function processChunk(array $products, string $context, string $user, string $timestamp): void
    {
        foreach ($products as $printifyId) {
            try {
                $this->syncService->syncProduct($printifyId);
                
                $this->logger->info('Product processed', [
                    'printify_id' => $printifyId,
                    'context' => $context,
                    'user' => $user
                ]);

            } catch (\Exception $e) {
                $this->logger->error('Failed to process product', [
                    'printify_id' => $printifyId,
                    'error' => $e->getMessage(),
                    'context' => $context
                ]);
            }
        }
    }
}