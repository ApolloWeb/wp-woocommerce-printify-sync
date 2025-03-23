<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class SyncTaskScheduler {
    public function __construct() {
        add_action('wpps_schedule_sync_tasks', [$this, 'scheduleTasks']);
        add_action('wpps_sync_batch', [$this, 'processBatch']);
    }

    public function scheduleTasks(): void {
        if (!as_next_scheduled_action('wpps_sync_batch')) {
            as_schedule_recurring_action(
                strtotime('midnight tonight'),
                DAY_IN_SECONDS,
                'wpps_sync_batch',
                [],
                'printify-sync'
            );
        }
    }

    public function processBatch(array $args = []): void {
        $batch_size = apply_filters('wpps_sync_batch_size', 20);
        $items = $this->getItemsToSync($batch_size);

        foreach ($items as $item) {
            as_enqueue_async_action(
                'wpps_process_sync_item',
                ['item_id' => $item->id, 'type' => $item->type],
                'printify-sync'
            );
        }
    }
}
