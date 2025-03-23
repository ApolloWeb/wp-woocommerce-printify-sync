<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class SyncRecovery {
    private $logger;
    private $event_tracker;

    public function __construct(Logger $logger, SyncEventTracker $event_tracker) {
        $this->logger = $logger;
        $this->event_tracker = $event_tracker;
    }

    public function recoverFailedSyncs(): void {
        global $wpdb;
        
        $failed_syncs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wpps_sync_queue 
            WHERE status = 'failed' 
            AND attempts < 3
            ORDER BY priority DESC, created_at ASC
            LIMIT 50"
        );

        foreach ($failed_syncs as $sync) {
            $this->retrySyncItem($sync);
        }
    }

    private function retrySyncItem($sync): void {
        try {
            do_action('wpps_retry_sync', $sync->type, maybe_unserialize($sync->payload));
            $this->event_tracker->trackEvent('sync_recovered', [
                'id' => $sync->id,
                'type' => $sync->type
            ]);
        } catch (\Exception $e) {
            $this->logger->log("Sync recovery failed: " . $e->getMessage(), 'error');
        }
    }
}
