<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImportMaintenanceService
{
    private string $currentTime = '2025-03-15 19:47:59';
    private string $currentUser = 'ApolloWeb';
    private ImportProgressTracker $tracker;

    public function __construct()
    {
        $this->tracker = new ImportProgressTracker();

        // Register cleanup schedule
        add_action('init', [$this, 'registerSchedules']);
        add_action('wpwps_cleanup_old_imports', [$this, 'cleanupOldImports']);

        // Register retry handler
        add_action('wp_ajax_wpwps_retry_failed_chunks', [$this, 'handleRetry']);
    }

    public function registerSchedules(): void
    {
        if (!wp_next_scheduled('wpwps_cleanup_old_imports')) {
            wp_schedule_event(time(), 'daily', 'wpwps_cleanup_old_imports');
        }
    }

    public function cleanupOldImports(): void
    {
        global $wpdb;

        // Keep last 30 days of imports
        $retentionPeriod = 30;
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionPeriod} days"));

        // Begin transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Get old batches
            $oldBatches = $wpdb->get_col($wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}wpwps_import_batches
                WHERE created_at < %s
                AND status IN ('completed', 'completed_with_errors')
            ", $cutoffDate));

            if (!empty($oldBatches)) {
                // Delete related chunks
                $batchIds = implode(',', array_map('intval', $oldBatches));
                $wpdb->query("
                    DELETE FROM {$wpdb->prefix}wpwps_import_chunks
                    WHERE batch_id IN ($batchIds)
                ");

                // Delete batches
                $wpdb->query("
                    DELETE FROM {$wpdb->prefix}wpwps_import_batches
                    WHERE id IN ($batchIds)
                ");
            }

            // Clean up orphaned chunks
            $wpdb->query($wpdb->prepare("
                DELETE FROM {$wpdb->prefix}wpwps_import_chunks
                WHERE created_at < %s
                AND batch_id NOT IN (
                    SELECT id FROM {$wpdb->prefix}wpwps_import_batches
                )
            ", $cutoffDate));

            $wpdb->query('COMMIT');

            // Log cleanup
            error_log(sprintf(
                '[WPWPS] Cleaned up %d old import batches - %s',
                count($oldBatches),
                $this->currentTime
            ));

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log(sprintf(
                '[WPWPS] Cleanup failed: %s - %s',
                $e->getMessage(),
                $this->currentTime
            ));
        }
    }

    public function handleRetry(): void
    {
        check_ajax_referer('wpwps_import');

        $batchId = (int) $_POST['batch_id'];
        $chunkIds = array_map('intval', (array) $_POST['chunk_ids']);

        try {
            $retriedChunks = $this->retryFailedChunks($batchId, $chunkIds);
            wp_send_json_success([
                'message' => sprintf('%d chunks queued for retry', count($retriedChunks)),
                'retried_chunks' => $retriedChunks
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    private function retryFailedChunks(int $batchId, array $chunkIds): array
    {
        global $wpdb;

        $retriedChunks = [];

        foreach ($chunkIds as $chunkId) {
            $chunk = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}wpwps_import_chunks
                WHERE batch_id = %d AND chunk_index = %d
            ", $batchId, $chunkId));

            if (!$chunk || $chunk->status !== 'failed') {
                continue;
            }

            // Reset chunk status
            $wpdb->update(
                $wpdb->prefix . 'wpwps_import_chunks',
                [
                    'status' => 'pending',
                    'error' => null,
                    'completed_at' => null,
                    'retry_count' => ($chunk->retry_count ?? 0) + 1,
                    'last_retry' => $this->currentTime
                ],
                [
                    'batch_id' => $batchId,
                    'chunk_index' => $chunkId
                ]
            );

            // Queue for processing
            $products = json_decode($chunk->products, true);
            foreach ($products as $product) {
                wp_schedule_single_event(
                    time(),
                    'wpwps_process_import_queue',
                    ['product_id' => $product, 'batch_id' => $batchId, 'chunk_index' => $chunkId]
                );
            }

            $retriedChunks[] = $chunkId;
        }

        // Update batch status if chunks were retried
        if (!empty($retriedChunks)) {
            $wpdb->update(
                $wpdb->prefix . 'wpwps_import_batches',
                [
                    'status' => 'processing',
                    'last_updated' => $this->currentTime
                ],
                ['id' => $batchId]
            );
        }

        return $retriedChunks;
    }
}