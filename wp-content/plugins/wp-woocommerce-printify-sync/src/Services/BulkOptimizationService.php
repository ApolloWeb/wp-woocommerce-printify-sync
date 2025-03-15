<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class BulkOptimizationService
{
    private string $currentTime = '2025-03-15 19:24:02';
    private string $currentUser = 'ApolloWeb';
    private ImageOptimizationService $optimizer;
    private int $batchSize = 5;
    private array $stats;

    public function __construct()
    {
        $this->optimizer = new ImageOptimizationService();
        $this->stats = [
            'processed' => 0,
            'optimized' => 0,
            'failed' => 0,
            'skipped' => 0,
            'total_savings' => 0
        ];

        add_action('wpwps_bulk_optimize_batch', [$this, 'processBatch']);
        add_action('wpwps_bulk_optimization_complete', [$this, 'handleComplete']);
    }

    public function startBulkOptimization(array $options = []): void
    {
        $query = $this->getUnoptimizedImages($options);
        $totalImages = $query->found_posts;

        if ($totalImages === 0) {
            return;
        }

        // Store bulk optimization settings
        update_option('wpwps_bulk_optimization', [
            'total' => $totalImages,
            'processed' => 0,
            'started_at' => $this->currentTime,
            'started_by' => $this->currentUser,
            'options' => $options,
            'status' => 'running'
        ]);

        // Schedule first batch
        wp_schedule_single_event(
            time(),
            'wpwps_bulk_optimize_batch',
            [$options]
        );
    }

    public function processBatch(array $options): void
    {
        $bulkStatus = get_option('wpwps_bulk_optimization');
        if ($bulkStatus['status'] !== 'running') {
            return;
        }

        $query = $this->getUnoptimizedImages($options);
        $images = $query->get_posts();

        foreach ($images as $image) {
            try {
                $this->processImage($image->ID);
                $bulkStatus['processed']++;
                
                // Update progress
                $progress = round(($bulkStatus['processed'] / $bulkStatus['total']) * 100);
                $this->updateProgress($progress, $bulkStatus);

            } catch (\Exception $e) {
                error_log("Bulk optimization failed for image {$image->ID}: " . $e->getMessage());
                $this->stats['failed']++;
            }
        }

        // Schedule next batch or complete
        if ($bulkStatus['processed'] < $bulkStatus['total']) {
            wp_schedule_single_event(
                time() + 10,
                'wpwps_bulk_optimize_batch',
                [$options]
            );
        } else {
            do_action('wpwps_bulk_optimization_complete', $this->stats);
        }

        // Store updated stats
        update_option('wpwps_bulk_optimization_stats', $this->stats);
    }

    private function processImage(int $attachmentId): void
    {
        $originalSize = $this->getAttachmentSize($attachmentId);

        // Check if already optimized
        if ($this->isAlreadyOptimized($attachmentId)) {
            $this->stats['skipped']++;
            return;
        }

        // Optimize image
        $this->optimizer->optimizeImage($attachmentId);

        // Calculate savings
        $newSize = $this->getAttachmentSize($attachmentId);
        $saved = $originalSize - $newSize;

        if ($saved > 0) {
            $this->stats['optimized']++;
            $this->stats['total_savings'] += $saved;
            
            update_post_meta($attachmentId, '_wpwps_bulk_optimized', true);
            update_post_meta($attachmentId, '_wpwps_optimization_savings', $saved);
        }

        $this->stats['processed']++;
    }

    private function getUnoptimizedImages(array $options): \WP_Query
    {
        $args = [
            'post_type' => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'posts_per_page' => $this->batchSize,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_wpwps_bulk_optimized',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ];

        // Add date filter if specified
        if (!empty($options['date_from'])) {
            $args['date_query'] = [
                'after' => $options['date_from']
            ];
        }

        // Add size filter if specified
        if (!empty($options['min_size'])) {
            $args['meta_query'][] = [
                'key' => '_wp_attached_file_size',
                'value' => $options['min_size'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            ];
        }

        return new \WP_Query($args);
    }

    private function updateProgress(int $progress, array &$bulkStatus): void
    {
        $bulkStatus['progress'] = $progress;
        $bulkStatus['last_updated'] = $this->currentTime;
        $bulkStatus['stats'] = $this->stats;

        update_option('wpwps_bulk_optimization', $bulkStatus);

        // Send progress to admin
        $this->notifyProgress($progress);
    }

    private function notifyProgress(int $progress): void
    {
        $notification = [
            'type' => 'optimization_progress',
            'progress' => $progress,
            'stats' => $this->stats,
            'timestamp' => $this->currentTime
        ];

        wp_cache_set('wpwps_optimization_progress', $notification, '', 30);
    }

    public function handleComplete(array $stats): void
    {
        $bulkStatus = get_option('wpwps_bulk_optimization');
        $bulkStatus['status'] = 'completed';
        $bulkStatus['completed_at'] = $this->currentTime;
        $bulkStatus['final_stats'] = $stats;

        update_option('wpwps_bulk_optimization', $bulkStatus);

        // Send completion email
        $this->sendCompletionEmail($stats);

        // Cleanup
        $this->cleanup();
    }

    private function sendCompletionEmail(array $stats): void
    {
        $to = get_option('admin_email');
        $subject = 'Bulk Image Optimization Complete';
        
        $message = sprintf(
            "Bulk image optimization completed at %s\n\n" .
            "Results:\n" .
            "- Processed: %d images\n" .
            "- Optimized: %d images\n" .
            "- Skipped: %d images\n" .
            "- Failed: %d images\n" .
            "- Total space saved: %s\n",
            $this->currentTime,
            $stats['processed'],
            $stats['optimized'],
            $stats['skipped'],
            $stats['failed'],
            size_format($stats['total_savings'])
        );

        wp_mail($to, $subject, $message);
    }

    private function cleanup(): void
    {
        wp_cache_delete('wpwps_optimization_progress');
    }

    private function getAttachmentSize(int $attachmentId): int
    {
        $file = get_attached_file($attachmentId);
        return file_exists($file) ? filesize($file) : 0;
    }

    private function isAlreadyOptimized(int $attachmentId): bool
    {
        return (bool) get_post_meta($attachmentId, '_wpwps_bulk_optimized', true);
    }

    public function pauseOptimization(): void
    {
        $bulkStatus = get_option('wpwps_bulk_optimization');
        $bulkStatus['status'] = 'paused';
        $bulkStatus['paused_at'] = $this->currentTime;
        update_option('wpwps_bulk_optimization', $bulkStatus);
    }

    public function resumeOptimization(): void
    {
        $bulkStatus = get_option('wpwps_bulk_optimization');
        if ($bulkStatus['status'] === 'paused') {
            $bulkStatus['status'] = 'running';
            update_option('wpwps_bulk_optimization', $bulkStatus);

            // Resume processing
            wp_schedule_single_event(
                time(),
                'wpwps_bulk_optimize_batch',
                [$bulkStatus['options']]
            );
        }
    }
}