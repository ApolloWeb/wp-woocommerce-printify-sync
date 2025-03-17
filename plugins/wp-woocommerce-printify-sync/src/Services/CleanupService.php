<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Context\SyncContext;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class CleanupService
{
    private LoggerInterface $logger;
    private SyncContext $context;

    public function __construct(LoggerInterface $logger, SyncContext $context)
    {
        $this->logger = $logger;
        $this->context = $context;
    }

    public function cleanupSync(string $syncId): void
    {
        try {
            $this->cleanupTemporaryFiles($syncId);
            $this->updateQueueStatus($syncId);
            $this->cleanupOrphanedData($syncId);
            
            $this->logger->info('Cleanup completed', [
                'sync_id' => $syncId,
                'timestamp' => $this->context->getCurrentTime()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Cleanup failed', [
                'sync_id' => $syncId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function cleanupTemporaryFiles(string $syncId): void
    {
        global $wpdb;
        
        // Get all temp files from this sync
        $files = $wpdb->get_results($wpdb->prepare(
            "SELECT storage_path, webp_path FROM {$wpdb->prefix}wpwps_image_tracking 
            WHERE sync_id = %s AND sync_status = 'temporary'",
            $syncId
        ));

        foreach ($files as $file) {
            if ($file->storage_path && file_exists($file->storage_path)) {
                @unlink($file->storage_path);
            }
            if ($file->webp_path && file_exists($file->webp_path)) {
                @unlink($file->webp_path);
            }
        }
    }

    private function updateQueueStatus(string $syncId): void
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'wpwps_queue_tracking',
            [
                'status' => 'completed',
                'completed_at' => $this->context->getCurrentTime(),
                'updated_at' => $this->context->getCurrentTime(),
                'updated_by' => $this->context->getCurrentUser()
            ],
            ['sync_id' => $syncId]
        );
    }

    private function cleanupOrphanedData(string $syncId): void
    {
        global $wpdb;

        // Remove orphaned variants
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}wpwps_variant_tracking 
            WHERE sync_id = %s AND variation_id NOT IN (
                SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product_variation'
            )",
            $syncId
        ));

        // Remove orphaned images
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}wpwps_image_tracking 
            WHERE sync_id = %s AND attachment_id NOT IN (
                SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment'
            )",
            $syncId
        ));
    }
}