<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImportProcessor
{
    private string $currentTime = '2025-03-15 19:39:06';
    private string $currentUser = 'ApolloWeb';
    
    public function __construct()
    {
        // Register webhook endpoint
        add_action('rest_api_init', [$this, 'registerWebhookEndpoint']);
        
        // Register cron handler
        add_action('wpwps_process_import_queue', [$this, 'processQueue']);
        
        // Register activation hook for manual import setup
        register_activation_hook(WPWPS_PLUGIN_FILE, [$this, 'setupManualImport']);
    }

    public function registerWebhookEndpoint(): void
    {
        register_rest_route('wpwps/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handleWebhook'],
            'permission_callback' => [$this, 'validateWebhook']
        ]);
    }

    public function validateWebhook(\WP_REST_Request $request): bool
    {
        $signature = $request->get_header('X-Printify-Signature');
        $secret = get_option('wpwps_webhook_secret');

        if (!$signature || !$secret) {
            return false;
        }

        $payload = $request->get_body();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    public function handleWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload = $request->get_json_params();

        try {
            // Queue the import job
            $this->queueImport($payload);

            // Schedule immediate cron event if not already scheduled
            if (!wp_next_scheduled('wpwps_process_import_queue')) {
                wp_schedule_single_event(time(), 'wpwps_process_import_queue');
            }

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Import queued successfully'
            ], 200);

        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function queueImport(array $payload): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_import_queue',
            [
                'payload' => json_encode($payload),
                'status' => 'pending',
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ]
        );
    }

    public function processQueue(): void
    {
        global $wpdb;

        // Get pending imports
        $imports = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpwps_import_queue 
                WHERE status = 'pending' 
                ORDER BY created_at ASC 
                LIMIT 5"
            )
        );

        if (empty($imports)) {
            return;
        }

        foreach ($imports as $import) {
            try {
                // Update status to processing
                $wpdb->update(
                    $wpdb->prefix . 'wpwps_import_queue',
                    ['status' => 'processing', 'started_at' => $this->currentTime],
                    ['id' => $import->id]
                );

                // Process the import
                $payload = json_decode($import->payload, true);
                $this->processImport($payload);

                // Mark as completed
                $wpdb->update(
                    $wpdb->prefix . 'wpwps_import_queue',
                    [
                        'status' => 'completed',
                        'completed_at' => $this->currentTime,
                        'result' => json_encode(['success' => true])
                    ],
                    ['id' => $import->id]
                );

            } catch (\Exception $e) {
                // Log error and mark as failed
                $wpdb->update(
                    $wpdb->prefix . 'wpwps_import_queue',
                    [
                        'status' => 'failed',
                        'completed_at' => $this->currentTime,
                        'result' => json_encode([
                            'success' => false,
                            'error' => $e->getMessage()
                        ])
                    ],
                    ['id' => $import->id]
                );
            }
        }

        // Schedule next batch if there are more pending imports
        $pendingCount = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_import_queue WHERE status = 'pending'"
            )
        );

        if ($pendingCount > 0) {
            wp_schedule_single_event(time() + 30, 'wpwps_process_import_queue');
        }
    }

    private function processImport(array $payload): void
    {
        $productService = new ProductImportService();
        $productService->importProduct($payload);
    }

    public function setupManualImport(): void
    {
        // Create import queue table
        $this->createQueueTable();

        // Register cron schedule
        if (!wp_next_scheduled('wpwps_process_import_queue')) {
            wp_schedule_single_event(time(), 'wpwps_process_import_queue');
        }
    }

    private function createQueueTable(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_import_queue (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            payload longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            created_by varchar(60) NOT NULL,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            result longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}