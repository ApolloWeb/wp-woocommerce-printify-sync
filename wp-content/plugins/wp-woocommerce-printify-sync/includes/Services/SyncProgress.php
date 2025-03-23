<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class SyncProgress {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpps_sync_progress';
    }

    public function startSync(string $type, int $total_items): int {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            [
                'type' => $type,
                'total_items' => $total_items,
                'processed_items' => 0,
                'failed_items' => 0,
                'status' => 'processing',
                'started_at' => current_time('mysql')
            ]
        );

        return $wpdb->insert_id;
    }

    public function updateProgress(int $sync_id, array $data): void {
        global $wpdb;
        
        $wpdb->update(
            $this->table_name,
            array_merge($data, ['updated_at' => current_time('mysql')]),
            ['id' => $sync_id]
        );

        do_action('wpps_sync_progress_updated', $sync_id, $data);
    }

    public function completeSync(int $sync_id, string $status = 'completed'): void {
        $this->updateProgress($sync_id, [
            'status' => $status,
            'completed_at' => current_time('mysql')
        ]);
    }
}
