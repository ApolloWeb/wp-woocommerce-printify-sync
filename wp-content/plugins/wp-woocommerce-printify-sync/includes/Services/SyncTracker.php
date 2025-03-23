<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class SyncTracker {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpps_sync_tracking';
    }

    public function recordSync(string $type, int $item_id, string $status, ?string $message = null): void {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            [
                'type' => $type,
                'item_id' => $item_id,
                'status' => $status,
                'message' => $message,
                'created_at' => current_time('mysql')
            ]
        );
    }

    public function getItemStatus(string $type, int $item_id): ?array {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE type = %s AND item_id = %d 
                ORDER BY created_at DESC 
                LIMIT 1",
                $type,
                $item_id
            ),
            ARRAY_A
        );
    }
}
