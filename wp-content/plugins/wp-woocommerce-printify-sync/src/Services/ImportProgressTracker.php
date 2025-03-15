<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImportProgressTracker
{
    private string $currentTime = '2025-03-15 19:41:11';
    private string $currentUser = 'ApolloWeb';
    private const CHUNK_SIZE = 10;

    public function initializeImport(array $productIds): int
    {
        global $wpdb;

        // Create batch record
        $batchId = $this->createBatch(count($productIds));

        // Split products into chunks and queue them
        $chunks = array_chunk($productIds, self::CHUNK_SIZE);
        
        foreach ($chunks as $index => $chunk) {
            $wpdb->insert(
                $wpdb->prefix . 'wpwps_import_chunks',
                [
                    'batch_id' => $batchId,
                    'chunk_index' => $index,
                    'total_chunks' => count($chunks),
                    'products' => json_encode($chunk),
                    'status' => 'pending',
                    'created_at' => $this->currentTime,
                    'created_by' => $this->currentUser
                ]
            );
        }

        return $batchId;
    }

    private function createBatch(int $totalProducts): int
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_import_batches',
            [
                'total_products' => $totalProducts,
                'status' => 'processing',
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser,
                'last_updated' => $this->currentTime
            ]
        );

        return $wpdb->insert_id;
    }

    public function updateChunkProgress(int $batchId, int $chunkIndex, string $status, ?string $error = null): void
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'wpwps_import_chunks',
            [
                'status' => $status,
                'completed_at' => $this->currentTime,
                'error' => $error
            ],
            [
                'batch_id' => $batchId,
                'chunk_index' => $chunkIndex
            ]
        );

        $this->updateBatchProgress($batchId);
    }

    private function updateBatchProgress(int $batchId): void
    {
        global $wpdb;

        // Get chunk statistics
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_chunks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_chunks,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_chunks
            FROM {$wpdb->prefix}wpwps_import_chunks
            WHERE batch_id = %d
        ", $batchId));

        // Calculate progress
        $progress = ($stats->completed_chunks / $stats->total_chunks) * 100;
        $status = $this->determineBatchStatus($stats);

        // Update batch record
        $wpdb->update(
            $wpdb->prefix . 'wpwps_import_batches',
            [
                'progress' => $progress,
                'status' => $status,
                'completed_chunks' => $stats->completed_chunks,
                'failed_chunks' => $stats->failed_chunks,
                'last_updated' => $this->currentTime
            ],
            ['id' => $batchId]
        );

        // Store detailed progress in transient for quick access
        $this->updateProgressCache($batchId, $progress, $status, $stats);
    }

    private function determineBatchStatus($stats): string
    {
        if ($stats->completed_chunks + $stats->failed_chunks === $stats->total_chunks) {
            return $stats->failed_chunks > 0 ? 'completed_with_errors' : 'completed';
        }
        return 'processing';
    }

    private function updateProgressCache(int $batchId, float $progress, string $status, object $stats): void
    {
        $progressData = [
            'progress' => round($progress, 2),
            'status' => $status,
            'stats' => [
                'total_chunks' => $stats->total_chunks,
                'completed_chunks' => $stats->completed_chunks,
                'failed_chunks' => $stats->failed_chunks
            ],
            'last_updated' => $this->currentTime
        ];

        set_transient("wpwps_import_progress_{$batchId}", $progressData, HOUR_IN_SECONDS);
    }

    public function getProgress(int $batchId): array
    {
        // Try to get from cache first
        $cached = get_transient("wpwps_import_progress_{$batchId}");
        if ($cached !== false) {
            return $cached;
        }

        // If not in cache, get from database
        global $wpdb;
        $batch = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}wpwps_import_batches WHERE id = %d
        ", $batchId));

        if (!$batch) {
            return [
                'progress' => 0,
                'status' => 'not_found',
                'stats' => [
                    'total_chunks' => 0,
                    'completed_chunks' => 0,
                    'failed_chunks' => 0
                ],
                'last_updated' => $this->currentTime
            ];
        }

        return [
            'progress' => round($batch->progress, 2),
            'status' => $batch->status,
            'stats' => [
                'total_chunks' => $batch->total_chunks,
                'completed_chunks' => $batch->completed_chunks,
                'failed_chunks' => $batch->failed_chunks
            ],
            'last_updated' => $batch->last_updated
        ];
    }

    public function getChunkDetails(int $batchId): array
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                chunk_index,
                status,
                error,
                created_at,
                completed_at
            FROM {$wpdb->prefix}wpwps_import_chunks
            WHERE batch_id = %d
            ORDER BY chunk_index ASC
        ", $batchId), ARRAY_A);
    }
}