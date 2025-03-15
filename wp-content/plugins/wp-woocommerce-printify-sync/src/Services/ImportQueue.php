<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImportQueue
{
    private string $currentTime = '2025-03-15 19:50:00';
    private string $currentUser = 'ApolloWeb';
    private const CHUNK_SIZE = 10;

    public function __construct()
    {
        add_action('wpwps_process_import_queue', [$this, 'processQueue']);
    }

    public function queueProduct(array $payload): int
    {
        global $wpdb;

        // Create new batch
        $batchId = $this->createBatch();

        // Queue the product
        $wpdb->insert(
            $wpdb->prefix . 'wpwps_import_queue',
            [
                'batch_id' => $batchId,
                'product_data' => json_encode($payload),
                'status' => 'pending',
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ]
        );

        // Schedule processing
        if (!wp_next_scheduled('wpwps_process_import_queue')) {
            wp_schedule_single_event(time(), 'wpwps_process_import_queue');
        }

        return $batchId;
    }

    private function createBatch(): int
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_import_batches',
            [
                'status' => 'pending',
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ]
        );

        return $wpdb->insert_id;
    }

    public function processQueue(): void
    {
        global $wpdb;

        // Get pending imports
        $imports = $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}wpwps_import_queue
                WHERE status = 'pending'
                ORDER BY created_at ASC
                LIMIT %d
            ", self::CHUNK_SIZE)
        );

        if (empty($imports)) {
            return;
        }

        foreach ($imports as $import) {
            try {
                // Update status to processing
                $wpdb->update(
                    $wpdb->prefix . 'wpwps_import_queue',
                    ['status' => 'processing'],
                    ['id' => $import->id]
                );

                // Process the import
                $productData = json_decode($import->product_data, true);
                $productImporter = new ProductImporter();
                $result = $productImporter->importProduct($productData);

                // Update status based on result
                $wpdb->update(
                    $wpdb->prefix . 'wpwps_import_queue',
                    [
                        'status' => 'completed',
                        'result' => json_encode($result),
                        'completed_at' => $this->currentTime
                    ],
                    ['id' => $import->id]
                );

            } catch (\Exception $e) {
                // Log error and update status
                error_log(sprintf(
                    '[WPWPS] Import failed for queue ID %d: %s - %s',
                    $import->id,
                    $e->getMessage(),
                    $this->currentTime
                ));

                $wpdb->update(
                    $wpdb->prefix . 'wpwps_import_queue',
                    [
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                        'completed_at' => $this->currentTime
                    ],
                    ['id' => $import->id]
                );
            }
        }

        // Check for more pending imports
        $pendingCount = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_import_queue
            WHERE status = 'pending'
        ");

        if ($pendingCount > 0) {
            wp_schedule_single_event(time() + 30, 'wpwps_process_import_queue');
        }
    }
}