<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class ProductSyncManager extends AbstractService
{
    private PrintifyAPI $api;
    private ProductImportService $importer;
    private int $batchSize;
    private int $totalProcessed = 0;

    public function __construct(
        PrintifyAPI $api,
        ProductImportService $importer,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        parent::__construct($logger, $config);
        $this->api = $api;
        $this->importer = $importer;
        $this->batchSize = (int)$this->config->get('import_batch_size', 20);
    }

    public function syncAllProducts(): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0
        ];

        try {
            $page = 1;
            do {
                // Fetch products from Printify
                $products = $this->api->getProducts([
                    'page' => $page,
                    'limit' => $this->batchSize
                ]);

                if (empty($products['data'])) {
                    break;
                }

                // Process batch
                $result = $this->processBatch($products['data']);
                
                // Update stats
                $stats['total'] += count($products['data']);
                $stats['success'] += count($result['success'] ?? []);
                $stats['failed'] += count($result['failed'] ?? []);
                $stats['skipped'] += count($result['skipped'] ?? []);

                // Schedule next batch if needed
                if ($this->shouldContinue($products)) {
                    $this->scheduleNextBatch($page + 1);
                    break;
                }

                $page++;

            } while (!empty($products['data']));

            $this->logOperation('syncAllProducts', [
                'message' => 'Product sync completed',
                'stats' => $stats
            ]);

            return $stats;

        } catch (\Exception $e) {
            $this->logError('syncAllProducts', $e);
            throw $e;
        }
    }

    private function processBatch(array $products): array
    {
        $result = [
            'success' => [],
            'failed' => [],
            'skipped' => []
        ];

        foreach ($products as $product) {
            try {
                // Check if product already exists
                $existingId = $this->getExistingProductId($product['id']);
                
                if ($existingId) {
                    // Check if product needs update
                    if ($this->needsUpdate($existingId, $product)) {
                        $this->importer->updateProduct($existingId, $product);
                        $result['success'][] = $product['id'];
                    } else {
                        $result['skipped'][] = $product['id'];
                    }
                } else {
                    // Create new product
                    $this->importer->importProducts([$product]);
                    $result['success'][] = $product['id'];
                }

                $this->totalProcessed++;

            } catch (\Exception $e) {
                $this->logError('processBatch', $e, [
                    'product_id' => $product['id']
                ]);
                $result['failed'][] = $product['id'];
            }
        }

        return $result;
    }

    private function needsUpdate(int $wooCommerceId, array $printifyProduct): bool
    {
        $lastSync = get_post_meta($wooCommerceId, '_printify_last_sync', true);
        return !$lastSync || strtotime($lastSync) < strtotime($printifyProduct['updated_at']);
    }

    private function shouldContinue(array $response): bool
    {
        // Check if we've hit processing limits
        if ($this->totalProcessed >= $this->config->get('max_products_per_run', 100)) {
            return true;
        }

        // Check rate limits
        $rateLimit = $this->api->getRateLimit();
        if ($rateLimit['remaining'] < $this->batchSize) {
            return true;
        }

        return false;
    }

    private function scheduleNextBatch(int $nextPage): void
    {
        as_schedule_single_action(
            time() + 300, // 5 minutes delay
            'wpwps_sync_products_batch',
            ['page' => $nextPage],
            'product-sync'
        );
    }

    private function getExistingProductId(string $printifyId): ?int
    {
        global $wpdb;
        
        $productId = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
            WHERE meta_key = '_printify_id' 
            AND meta_value = %s 
            LIMIT 1",
            $printifyId
        ));

        return $productId ? (int)$productId : null;
    }
}