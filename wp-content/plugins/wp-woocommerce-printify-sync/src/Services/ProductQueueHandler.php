<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\QueueHandlerInterface;

class ProductQueueHandler implements QueueHandlerInterface
{
    private string $currentTime = '2025-03-15 19:52:43';
    private string $currentUser = 'ApolloWeb';
    private const CHUNK_SIZE = 10;

    public function queue(array $data): int
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_queue',
            [
                'payload' => json_encode($data),
                'type' => 'product_import',
                'status' => 'pending',
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ]
        );

        $queueId = $wpdb->insert_id;

        if (!wp_next_scheduled('wpwps_process_queue')) {
            wp_schedule_single_event(time(), 'wpwps_process_queue', ['batch_id' => $queueId]);
        }

        return $queueId;
    }

    public function process(int $batchId): void
    {
        global $wpdb;

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpwps_queue WHERE id = %d",
            $batchId
        ));

        if (!$item || $item->status !== 'pending') {
            return;
        }

        try {
            $wpdb->update(
                $wpdb->prefix . 'wpwps_queue',
                ['status' => 'processing', 'started_at' => $this->currentTime],
                ['id' => $batchId]
            );

            $data = json_decode($item->payload, true);
            $importer = new ProductImporter();
            $result = $importer->import($data);

            $wpdb->update(
                $wpdb->prefix . 'wpwps_queue',
                [
                    'status' => 'completed',
                    'result' => json_encode($result),
                    'completed_at' => $this->currentTime
                ],
                ['id' => $batchId]
            );

        } catch (\Exception $e) {
            $wpdb->update(
                $wpdb->prefix . 'wpwps_queue',
                [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'completed_at' => $this->currentTime
                ],
                ['id' => $batchId]
            );
        }
    }

    public function getStatus(int $batchId): array
    {
        global $wpdb;

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpwps_queue WHERE id = %d",
            $batchId
        ));

        if (!$item) {
            return [
                'status' => 'not_found',
                'message' => 'Queue item not found'
            ];
        }

        return [
            'status' => $item->status,
            'created_at' => $item->created_at,
            'started_at' => $item->started_at,
            'completed_at' => $item->completed_at,
            'error' => $item->error,
            'result' => json_decode($item->result, true)
        ];
    }
}