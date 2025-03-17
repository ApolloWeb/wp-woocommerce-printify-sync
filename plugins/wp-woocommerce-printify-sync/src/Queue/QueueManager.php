<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Queue;

use ApolloWeb\WPWooCommercePrintifySync\Context\SyncContext;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class QueueManager
{
    public const CHUNK_SIZE = 10;
    private const GROUP_IMPORT = 'wpwps_product_import';
    private const GROUP_WEBHOOK = 'wpwps_webhook_updates';
    private const GROUP_CLEANUP = 'wpwps_cleanup';

    private LoggerInterface $logger;
    private SyncContext $context;

    public function __construct(LoggerInterface $logger, SyncContext $context)
    {
        $this->logger = $logger;
        $this->context = $context;
    }

    public function scheduleImport(array $productIds, string $shopId): string
    {
        $syncId = $this->generateSyncId('import');
        $chunks = array_chunk($productIds, self::CHUNK_SIZE);

        $this->trackQueue($syncId, $shopId, count($productIds));

        foreach ($chunks as $index => $chunk) {
            $batchId = $this->generateBatchId($syncId, $index);
            
            as_schedule_single_action(
                strtotime("+{$index} minutes", strtotime($this->context->getCurrentTime())),
                'wpwps_process_product_chunk',
                [
                    'products' => $chunk,
                    'shop_id' => $shopId,
                    'sync_id' => $syncId,
                    'batch_id' => $batchId,
                    ...$this->context->toArray()
                ],
                self::GROUP_IMPORT
            );
        }

        $this->scheduleCleanup($syncId);

        return $syncId;
    }

    public function scheduleWebhookUpdate(string $printifyId, string $shopId, string $event): string
    {
        $syncId = $this->generateSyncId('webhook');
        
        as_schedule_single_action(
            strtotime($this->context->getCurrentTime()),
            'wpwps_process_webhook_update',
            [
                'printify_id' => $printifyId,
                'shop_id' => $shopId,
                'event' => $event,
                'sync_id' => $syncId,
                ...$this->context->toArray()
            ],
            self::GROUP_WEBHOOK
        );

        return $syncId;
    }

    private function scheduleCleanup(string $syncId): void
    {
        as_schedule_single_action(
            strtotime('+1 hour', strtotime($this->context->getCurrentTime())),
            'wpwps_cleanup_sync',
            [
                'sync_id' => $syncId,
                ...$this->context->toArray()
            ],
            self::GROUP_CLEANUP
        );
    }

    private function trackQueue(string $syncId, string $shopId, int $totalItems): void
    {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'wpwps_queue_tracking',
            [
                'sync_id' => $syncId,
                'batch_id' => 'master',
                'shop_id' => $shopId,
                'total_items' => $totalItems,
                'status' => 'pending',
                'created_at' => $this->context->getCurrentTime(),
                'updated_at' => $this->context->getCurrentTime(),
                'created_by' => $this->context->getCurrentUser(),
                'updated_by' => $this->context->getCurrentUser()
            ]
        );
    }

    private function generateSyncId(string $type): string
    {
        return uniqid("{$type}_", true);
    }

    private function generateBatchId(string $syncId, int $index): string
    {
        return "{$syncId}_batch_{$index}";
    }
}