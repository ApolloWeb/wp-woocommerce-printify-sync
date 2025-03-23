<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class QueueManager {
    private $queue_table = 'wpps_queue';
    private $batch_size = 50;

    public function init(): void {
        add_action('wpps_process_queue', [$this, 'processQueue']);
    }

    public function addToQueue(string $task, array $data = []): bool {
        global $wpdb;
        return $wpdb->insert(
            $wpdb->prefix . $this->queue_table,
            [
                'task' => $task,
                'data' => maybe_serialize($data),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }

    public function processQueue(): void {
        // Queue processing implementation
    }
}
