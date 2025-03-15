<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class QueueProcessor
{
    private string $currentTime;
    private string $currentUser;
    private const QUEUE_TABLE = 'wpwps_queue';

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime; // 2025-03-15 18:19:14
        $this->currentUser = $currentUser; // ApolloWeb
        
        add_action('wpwps_process_queue', [$this, 'processQueue']);
    }

    public function addToQueue(string $task, array $data): int
    {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . self::QUEUE_TABLE,
            [
                'task' => $task,
                'data' => json_encode($data),
                'status' => 'pending',
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        if (!wp_next_scheduled('wpwps_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'wpwps_process_queue');
        }

        return $wpdb->insert_id;
    }

    public function processQueue(): void
    {
        global $wpdb;

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}" . self::QUEUE_TABLE . "
                WHERE status = 'pending'
                ORDER BY created_at ASC
                LIMIT 5"
            )
        );

        foreach ($items as $item) {
            try {
                $this->processItem($item);
                $this->markAsComplete($item->id);
            } catch (\Exception $e) {
                $this->markAsFailed($item->id, $e->getMessage());
            }
        }
    }

    private function processItem(object $item): void
    {
        $data = json_decode($item->data, true);
        
        switch ($item->task) {
            case 'import_product':
                $this->processProductImport($data);
                break;
            case 'sync_images':
                $this->processSyncImages($data);
                break;
            // Add more task types as needed
        }
    }

    private function markAsComplete(int $id): void
    {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . self::QUEUE_TABLE,
            [
                'status' => 'completed',
                'completed_at' => $this->currentTime,
                'completed_by' => $this->currentUser
            ],
            ['id' => $id],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    private function markAsFailed(int $id, string $error): void
    {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . self::QUEUE_TABLE,
            [
                'status' => 'failed',
                'error' => $error,
                'failed_at' => $this->currentTime
            ],
            ['id' => $id],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }
}