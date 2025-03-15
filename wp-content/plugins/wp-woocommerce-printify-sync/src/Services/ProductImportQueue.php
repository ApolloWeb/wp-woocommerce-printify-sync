<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ProductImportQueue
{
    private string $currentTime = '2025-03-15 19:18:35';
    private string $currentUser = 'ApolloWeb';
    private array $importLog = [];

    public function __construct()
    {
        // Register import hooks
        add_action('wpwps_process_import_queue', [$this, 'processQueue']);
        add_action('wpwps_import_complete', [$this, 'handleImportComplete']);
    }

    public function addToQueue(array $products): void
    {
        $queue = get_option('wpwps_import_queue', []);
        
        foreach ($products as $product) {
            $queue[] = [
                'product_id' => $product['id'],
                'added_at' => $this->currentTime,
                'added_by' => $this->currentUser,
                'status' => 'pending',
                'attempts' => 0
            ];
        }

        update_option('wpwps_import_queue', $queue);
    }

    public function processQueue(): void
    {
        $queue = get_option('wpwps_import_queue', []);
        $maxAttempts = 3;
        $batchSize = 5;
        $processed = 0;

        foreach ($queue as $key => &$item) {
            if ($processed >= $batchSize) {
                break;
            }

            if ($item['status'] !== 'pending' || $item['attempts'] >= $maxAttempts) {
                continue;
            }

            try {
                $item['attempts']++;
                $item['last_attempt'] = $this->currentTime;

                do_action('wpwps_before_product_import', $item);

                $result = $this->importProduct($item['product_id']);
                
                $item['status'] = 'completed';
                $item['completed_at'] = $this->currentTime;
                $item['result'] = $result;

                $this->logSuccess($item);
                $processed++;

            } catch (\Exception $e) {
                $item['status'] = $item['attempts'] >= $maxAttempts ? 'failed' : 'pending';
                $item['error'] = $e->getMessage();
                $this->logError($item);
            }

            do_action('wpwps_after_product_import', $item);
        }

        update_option('wpwps_import_queue', $queue);

        if ($this->isQueueComplete($queue)) {
            do_action('wpwps_import_complete');
        }
    }

    private function importProduct(string $productId): array
    {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');

            // Import core product data
            $product = $this->importCoreProduct($productId);
            
            // Import variations
            $this->importVariations($product);
            
            // Import images
            $this->importImages($product);
            
            // Import metadata
            $this->importMetadata($product);
            
            // Import tags and categories
            $this->importTaxonomies($product);

            $wpdb->query('COMMIT');

            return [
                'product_id' => $product->get_id(),
                'sku' => $product->get_sku(),
                'status' => 'success'
            ];

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    private function logSuccess(array $item): void
    {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wpwps_import_log',
            [
                'printify_id' => $item['product_id'],
                'status' => 'success',
                'imported_at' => $this->currentTime,
                'imported_by' => $this->currentUser,
                'details' => json_encode($item['result'])
            ]
        );
    }

    private function logError(array $item): void
    {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wpwps_import_log',
            [
                'printify_id' => $item['product_id'],
                'status' => 'error',
                'imported_at' => $this->currentTime,
                'imported_by' => $this->currentUser,
                'details' => json_encode([
                    'error' => $item['error'],
                    'attempts' => $item['attempts']
                ])
            ]
        );
    }

    public function handleImportComplete(): void
    {
        // Send notification
        $this->sendImportSummary();
        
        // Cleanup
        $this->cleanup();
        
        // Reset import flags
        update_option('wpwps_import_running', false);
    }

    private function sendImportSummary(): void
    {
        $log = $this->getImportLog();
        
        $summary = [
            'total' => count($log),
            'success' => count(array_filter($log, fn($item) => $item['status'] === 'success')),
            'failed' => count(array_filter($log, fn($item) => $item['status'] === 'error')),
            'timestamp' => $this->currentTime,
            'user' => $this->currentUser
        ];

        wp_mail(
            get_option('admin_email'),
            'Product Import Complete',
            $this->generateSummaryEmail($summary)
        );
    }

    private function cleanup(): void
    {
        // Clear temporary files
        $tempDir = wp_upload_dir()['basedir'] . '/wpwps-temp';
        if (is_dir($tempDir)) {
            array_map('unlink', glob("$tempDir/*.*"));
            rmdir($tempDir);
        }

        // Clear queue
        delete_option('wpwps_import_queue');
    }
}