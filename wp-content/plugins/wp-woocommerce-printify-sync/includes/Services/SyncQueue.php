<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class SyncQueue {
    private $table_name;
    private $max_attempts = 3;
    private $retry_delays = [30, 300, 900]; // 30s, 5m, 15m

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpps_sync_queue';
    }

    public function enqueue(string $type, array $payload, int $priority = 10): int {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            [
                'type' => $type,
                'payload' => maybe_serialize($payload),
                'priority' => $priority,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ]
        );

        return $wpdb->insert_id;
    }

    public function process(): void {
        $items = $this->getPendingItems();
        
        foreach ($items as $item) {
            try {
                $this->processItem($item);
            } catch (\Exception $e) {
                $this->handleFailure($item, $e);
            }
        }
    }

    private function processItem($item): void {
        global $wpdb;
        
        $wpdb->update(
            $this->table_name,
            [
                'status' => 'processing',
                'started_at' => current_time('mysql')
            ],
            ['id' => $item->id]
        );

        do_action('wpps_process_sync_item', $item->type, maybe_unserialize($item->payload));
        
        $wpdb->update(
            $this->table_name,
            [
                'status' => 'completed',
                'completed_at' => current_time('mysql')
            ],
            ['id' => $item->id]
        );
    }

    private function handleFailure($item, \Exception $e): void {
        global $wpdb;
        
        $attempts = $item->attempts + 1;
        $retry_after = $attempts <= count($this->retry_delays) 
            ? time() + $this->retry_delays[$attempts - 1]
            : null;

        $wpdb->update(
            $this->table_name,
            [
                'status' => $retry_after ? 'failed' : 'error',
                'attempts' => $attempts,
                'last_error' => $e->getMessage(),
                'retry_after' => $retry_after ? date('Y-m-d H:i:s', $retry_after) : null
            ],
            ['id' => $item->id]
        );
    }
}
