<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class SyncManager
{
    private string $currentTime;
    private string $currentUser;
    private PrintifyApiClient $apiClient;
    private ProductImportService $importService;

    public function __construct(
        PrintifyApiClient $apiClient,
        ProductImportService $importService
    ) {
        $this->currentTime = '2025-03-15 18:40:05';
        $this->currentUser = 'ApolloWeb';
        $this->apiClient = $apiClient;
        $this->importService = $importService;
    }

    public function syncProducts(string $shopId): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'started_at' => $this->currentTime,
            'completed_at' => null,
            'user' => $this->currentUser
        ];

        try {
            // Get products from Printify
            $products = $this->apiClient->request('products', 'GET', ['shop_id' => $shopId]);
            $stats['total'] = count($products);

            foreach ($products as $product) {
                try {
                    $this->importService->importProduct($product);
                    $stats['success']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $this->logError($e->getMessage(), $product);
                }
            }

        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }

        $stats['completed_at'] = date('Y-m-d H:i:s');
        $this->saveSyncStats($stats);

        return $stats;
    }

    private function logError(string $message, array $context = []): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_error_log',
            [
                'message' => $message,
                'context' => json_encode($context),
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ]
        );
    }

    private function saveSyncStats(array $stats): void
    {
        update_option('wpwps_last_sync', $stats);
    }
}