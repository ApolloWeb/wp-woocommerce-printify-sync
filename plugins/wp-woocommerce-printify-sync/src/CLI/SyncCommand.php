<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\CLI;

use ApolloWeb\WPWooCommercePrintifySync\Services\SyncService;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use WP_CLI;

class SyncCommand
{
    private SyncService $syncService;
    private LoggerInterface $logger;

    public function __construct(SyncService $syncService, LoggerInterface $logger)
    {
        $this->syncService = $syncService;
        $this->logger = $logger;
    }

    /**
     * Start a full sync from Printify
     *
     * ## OPTIONS
     *
     * [--shop-id=<shop_id>]
     * : The Printify shop ID. If not provided, uses the configured default.
     *
     * [--force]
     * : Force sync even if another sync is in progress
     *
     * ## EXAMPLES
     *
     *     wp printify sync start
     *     wp printify sync start --shop-id=123456
     *     wp printify sync start --force
     */
    public function start($args, $assoc_args): void
    {
        try {
            $shopId = $assoc_args['shop-id'] ?? get_option('wpwps_shop_id');
            $force = isset($assoc_args['force']);

            if (empty($shopId)) {
                WP_CLI::error('Shop ID not provided and no default configured');
            }

            if (!$force && $this->syncService->isSyncInProgress()) {
                WP_CLI::error('Sync already in progress. Use --force to override');
            }

            $syncId = $this->syncService->scheduleFullSync($shopId);

            WP_CLI::success(sprintf(
                'Sync scheduled successfully. Sync ID: %s',
                $syncId
            ));

        } catch (\Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }

    /**
     * Get the status of a sync
     *
     * ## OPTIONS
     *
     * <sync-id>
     * : The ID of the sync to check
     *
     * [--watch]
     * : Watch the sync progress in real-time
     *
     * ## EXAMPLES
     *
     *     wp printify sync status abc123
     *     wp printify sync status abc123 --watch
     */
    public function status($args, $assoc_args): void
    {
        try {
            $syncId = $args[0];
            $watch = isset($assoc_args['watch']);

            do {
                $status = $this->syncService->getSyncStatus($syncId);

                if ($watch) {
                    WP_CLI::line(sprintf(
                        '[%s] Progress: %d/%d (Failed: %d)',
                        $status['status'],
                        $status['processed'],
                        $status['total'],
                        $status['failed']
                    ));

                    if ($status['status'] === 'completed') {
                        break;
                    }

                    sleep(5);
                    continue;
                }

                WP_CLI\Utils\format_items(
                    'table',
                    [$status],
                    ['sync_id', 'status', 'total', 'processed', 'failed', 'started_at', 'completed_at']
                );
                break;

            } while ($watch);

        } catch (\Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }

    /**
     * Clean up old sync data
     *
     * ## OPTIONS
     *
     * [--older-than=<days>]
     * : Clean up data older than specified days (default: 30)
     *
     * [--dry-run]
     * : Show what would be cleaned up without actually doing it
     *
     * ## EXAMPLES
     *
     *     wp printify sync cleanup
     *     wp printify sync cleanup --older-than=7
     *     wp printify sync cleanup --dry-run
     */
    public function cleanup($args, $assoc_args): void
    {
        try {
            $days = (int)($assoc_args['older-than'] ?? 30);
            $dryRun = isset($assoc_args['dry-run']);

            $stats = $this->syncService->cleanup($days, $dryRun);

            if ($dryRun) {
                WP_CLI::line('Dry run results:');
            }

            WP_CLI\Utils\format_items(
                'table',
                [$stats],
                ['sync_records', 'images', 'variants', 'temp_files']
            );

        } catch (\Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }
}