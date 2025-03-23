<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class SyncJobManager {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpps_sync_jobs';
    }

    public function lockJob(string $job_id, string $type): bool {
        global $wpdb;
        
        return (bool) $wpdb->insert(
            $this->table_name,
            [
                'job_id' => $job_id,
                'type' => $type,
                'locked_at' => current_time('mysql'),
                'status' => 'processing'
            ],
            ['%s', '%s', '%s', '%s']
        );
    }

    public function unlockJob(string $job_id): void {
        global $wpdb;
        $wpdb->delete($this->table_name, ['job_id' => $job_id]);
    }

    public function isLocked(string $job_id): bool {
        global $wpdb;
        return (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE job_id = %s",
                $job_id
            )
        );
    }
}
