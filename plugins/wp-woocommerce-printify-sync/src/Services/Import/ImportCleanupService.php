<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Import;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class ImportCleanupService
{
    use TimeStampTrait;

    private const CLEANUP_SCHEDULE = 'wpwps_import_cleanup';
    private ImportMonitor $monitor;
    private LoggerInterface $logger;

    public function __construct(ImportMonitor $monitor, LoggerInterface $logger)
    {
        $this->monitor = $monitor;
        $this->logger = $logger;
    }

    public function register(): void
    {
        // Register daily cleanup
        if (!wp_next_scheduled(self::CLEANUP_SCHEDULE)) {
            wp_schedule_event(
                time(),
                'daily',
                self::CLEANUP_SCHEDULE
            );
        }

        add_action(self::CLEANUP_SCHEDULE, [$this, 'cleanup']);
    }

    public function cleanup(): void
    {
        try {
            $this->logger->info('Starting import cleanup', [
                'timestamp' => $this->getCurrentTime()
            ]);

            // Clean up monitor data
            $this->monitor->cleanup();

            // Clean up failed imports data
            $this->cleanupFailedImports();

            // Clean up temporary files
            $this->cleanupTempFiles();

            // Clean up orphaned actions
            $this->cleanupOrphanedActions();

            $this->logger->info('Import cleanup completed', [
                'timestamp' => $this->getCurrentTime()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Import cleanup failed', [
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
        }
    }

    private function cleanupFailedImports(): void
    {
        global $wpdb;
        
        $oldBatches = $wpdb->get_col(
            "SELECT option_name FROM $wpdb->options
            WHERE option_name LIKE 'wpwps_import_%_failed'
            AND option_name NOT IN (
                SELECT CONCAT('wpwps_import_', batch_id, '_failed')
                FROM {$wpdb->prefix}wpwps_import_status
                WHERE completed_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            )"
        );

        foreach ($oldBatches as $option) {
            delete_option($option);
        }
    }

    private function cleanupTempFiles(): void
    {
        $uploadDir = wp_upload_dir();
        $tempDir = $uploadDir['basedir'] . '/wpwps-temp';

        if (!is_dir($tempDir)) {
            return;
        }

        $files = glob($tempDir . '/*');
        $now = time();

        foreach ($files as $file) {
            if ($now - filemtime($file) > 86400) { // 24 hours
                unlink($file);
            }
        }
    }

    private function cleanupOrphanedActions(): void
    {
        $actions = as_get_scheduled_actions([
            'hook' => [
                'wpwps_import_products_chunk',
                'wpwps_import_product_images'
            ],
            'status' => ActionScheduler_Store::STATUS_PENDING
        ]);

        foreach ($actions as $action) {
            $args = $action->get_args();
            if (!isset($args['batch_id']) || !$this->isBatchValid($args['batch_id'])) {
                as_unschedule_action(
                    $action->get_hook(),
                    $action->get_args(),
                    $action->get_group()
                );
            }
        }
    }

    private function isBatchValid(string $batchId): bool
    {
        $status = $this->monitor->getImportStatus($batchId);
        return $status['status'] !== 'not_found';
    }
}