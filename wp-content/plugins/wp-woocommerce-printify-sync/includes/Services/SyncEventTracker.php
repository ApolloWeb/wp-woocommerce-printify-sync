<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class SyncEventTracker {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpps_sync_events';
    }

    public function trackEvent(string $event_type, array $data, string $status = 'success'): void {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            [
                'event_type' => $event_type,
                'data' => maybe_serialize($data),
                'status' => $status,
                'created_at' => current_time('mysql')
            ]
        );
    }

    public function getRecentEvents(int $limit = 10): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
    }
}
