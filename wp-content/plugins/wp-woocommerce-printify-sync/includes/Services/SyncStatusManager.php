<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class SyncStatusManager {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpps_sync_status';
    }

    public function updateStatus(int $item_id, string $type, string $status, ?string $error = null): void {
        global $wpdb;
        
        $data = [
            'status' => $status,
            'last_sync' => current_time('mysql'),
            'error' => $error,
            'attempts' => $wpdb->get_var($wpdb->prepare(
                "SELECT attempts FROM {$this->table_name} WHERE item_id = %d AND type = %s",
                $item_id,
                $type
            )) + 1
        ];

        $wpdb->replace(
            $this->table_name,
            array_merge(['item_id' => $item_id, 'type' => $type], $data)
        );
    }

    public function getFailedItems(int $limit = 50): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE status = 'failed' 
            AND attempts < 3 
            ORDER BY last_sync ASC 
            LIMIT %d",
            $limit
        ));
    }
}
