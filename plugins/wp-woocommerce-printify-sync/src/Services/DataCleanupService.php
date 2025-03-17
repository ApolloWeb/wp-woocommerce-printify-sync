<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class DataCleanupService
{
    use TimeStampTrait;

    private LoggerInterface $logger;
    private StorageManager $storage;

    public function __construct(LoggerInterface $logger, StorageManager $storage)
    {
        $this->logger = $logger;
        $this->storage = $storage;
    }

    public function cleanupAll(): array
    {
        try {
            // Start transaction
            global $wpdb;
            $wpdb->query('START TRANSACTION');

            // Track what's being cleaned
            $cleaned = [
                'products' => 0,
                'orders' => 0,
                'logs' => 0,
                'meta' => 0,
                'files' => 0
            ];

            // Clean products
            $cleaned['products'] = $this->cleanupProducts();

            // Clean orders
            $cleaned['orders'] = $this->cleanupOrders();

            // Clean logs
            $cleaned['logs'] = $this->cleanupLogs();

            // Clean meta data
            $cleaned['meta'] = $this->cleanupMeta();

            // Clean stored files
            $cleaned['files'] = $this->cleanupFiles();

            // Commit transaction
            $wpdb->query('COMMIT');

            // Log the cleanup
            $this->logger->info('Data cleanup completed', [
                'cleaned' => $cleaned,
                'user' => $this->getCurrentUser(),
                'timestamp' => $this->getCurrentTime()
            ]);

            return [
                'success' => true,
                'message' => 'Data cleanup completed successfully',
                'cleaned' => $cleaned
            ];

        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');

            $this->logger->error('Data cleanup failed', [
                'error' => $e->getMessage(),
                'user' => $this->getCurrentUser(),
                'timestamp' => $this->getCurrentTime()
            ]);

            return [
                'success' => false,
                'message' => 'Data cleanup failed: ' . $e->getMessage()
            ];
        }
    }

    private function cleanupProducts(): int
    {
        global $wpdb;
        $count = 0;

        // Get all products with printify meta
        $products = $wpdb->get_results("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_printify_%'
        ");

        foreach ($products as $product) {
            wp_delete_post($product->post_id, true);
            $count++;
        }

        return $count;
    }

    private function cleanupOrders(): int
    {
        global $wpdb;
        $count = 0;

        // Get all orders with printify meta
        $orders = $wpdb->get_results("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_printify_%'
            AND post_id IN (
                SELECT ID 
                FROM {$wpdb->posts} 
                WHERE post_type = 'shop_order'
            )
        ");

        foreach ($orders as $order) {
            wp_delete_post($order->post_id, true);
            $count++;
        }

        return $count;
    }

    private function cleanupLogs(): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'printify_logs';
        
        $count = $wpdb->query("TRUNCATE TABLE {$table}");
        
        return $count !== false ? $count : 0;
    }

    private function cleanupMeta(): int
    {
        global $wpdb;
        
        return $wpdb->query("
            DELETE FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_printify_%'
        ");
    }

    private function cleanupFiles(): int
    {
        $count = 0;

        // Clean Google Drive
        try {
            $files = $this->storage->list('google_drive', 'printify/');
            foreach ($files as $file) {
                $this->storage->delete('google_drive', $file['path']);
                $count++;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to cleanup Google Drive files', [
                'error' => $e->getMessage()
            ]);
        }

        // Clean R2
        try {
            $files = $this->storage->list('r2', 'printify/');
            foreach ($files as $file) {
                $this->storage->delete('r2', $file['path']);
                $count++;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to cleanup R2 files', [
                'error' => $e->getMessage()
            ]);
        }

        return $count;
    }
}