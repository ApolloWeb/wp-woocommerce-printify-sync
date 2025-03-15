<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ImportProgress
{
    private string $currentTime = '2025-03-15 19:41:11';
    private string $currentUser = 'ApolloWeb';
    private ImportProgressTracker $tracker;

    public function __construct()
    {
        $this->tracker = new ImportProgressTracker();
        add_action('wp_ajax_wpwps_check_import_progress', [$this, 'checkProgress']);
    }

    public function checkProgress(): void
    {
        check_ajax_referer('wpwps_import');

        $batchId = (int) $_POST['batch_id'];
        $progress = $this->tracker->getProgress($batchId);

        if ($progress['status'] === 'not_found') {
            wp_send_json_error(['message' => 'Import batch not found']);
            return;
        }

        // Get chunk details if import is complete
        $chunks = [];
        if (in_array($progress['status'], ['completed', 'completed_with_errors'])) {
            $chunks = $this->tracker->getChunkDetails($batchId);
        }

        wp_send_json_success([
            'progress' => $progress,
            'chunks' => $chunks,
            'timestamp' => $this->currentTime
        ]);
    }
}