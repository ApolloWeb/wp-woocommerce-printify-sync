<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class JobService {
    private $logger_service;

    public function __construct() {
        $this->logger_service = new LoggerService();

        add_action('init', [$this, 'registerSchedules']);
        add_action('wpwps_batch_process_queue', [$this, 'processBatchQueue']);
        add_action('wpwps_retry_failed_jobs', [$this, 'retryFailedJobs']);
    }

    public function registerSchedules(): void {
        if (!wp_next_scheduled('wpwps_batch_process_queue')) {
            wp_schedule_event(time(), 'every_5_minutes', 'wpwps_batch_process_queue');
        }

        if (!wp_next_scheduled('wpwps_retry_failed_jobs')) {
            wp_schedule_event(time(), 'daily', 'wpwps_retry_failed_jobs');
        }
    }

    public function queueJob(string $job_type, array $data = [], ?int $delay = null): bool {
        global $wpdb;

        $job_data = [
            'job_type' => $job_type,
            'data' => maybe_serialize($data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'scheduled_for' => $delay ? 
                date('Y-m-d H:i:s', time() + $delay) : 
                current_time('mysql')
        ];

        $result = $wpdb->insert(
            $wpdb->prefix . 'wpwps_job_queue',
            $job_data,
            ['%s', '%s', '%s', '%s', '%s']
        );

        if ($result) {
            do_action('wpwps_log_info', 'Job queued', [
                'job_type' => $job_type,
                'job_id' => $wpdb->insert_id
            ]);
            return true;
        }

        do_action('wpwps_log_error', 'Failed to queue job', [
            'job_type' => $job_type,
            'error' => $wpdb->last_error
        ]);
        return false;
    }

    public function processBatchQueue(): void {
        global $wpdb;

        // Get pending jobs
        $jobs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpwps_job_queue 
            WHERE status = 'pending' 
            AND scheduled_for <= %s 
            AND attempts < 3 
            ORDER BY scheduled_for ASC 
            LIMIT 50",
            current_time('mysql')
        ));

        foreach ($jobs as $job) {
            try {
                $this->processJob($job);
                
                // Mark as completed
                $wpdb->update(
                    $wpdb->prefix . 'wpwps_job_queue',
                    [
                        'status' => 'completed',
                        'completed_at' => current_time('mysql')
                    ],
                    ['id' => $job->id],
                    ['%s', '%s'],
                    ['%d']
                );
            } catch (\Exception $e) {
                // Mark as failed
                $wpdb->update(
                    $wpdb->prefix . 'wpwps_job_queue',
                    [
                        'status' => 'failed',
                        'attempts' => $job->attempts + 1,
                        'last_error' => $e->getMessage()
                    ],
                    ['id' => $job->id],
                    ['%s', '%d', '%s'],
                    ['%d']
                );

                do_action('wpwps_log_error', 'Job processing failed', [
                    'job_id' => $job->id,
                    'job_type' => $job->job_type,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Clean up old completed jobs
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}wpwps_job_queue 
            WHERE status = 'completed' 
            AND completed_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
    }

    public function retryFailedJobs(): void {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'wpwps_job_queue',
            [
                'status' => 'pending',
                'attempts' => 0,
                'last_error' => null,
                'scheduled_for' => current_time('mysql')
            ],
            [
                'status' => 'failed',
                'attempts' => ['<', 3]
            ],
            ['%s', '%d', null, '%s'],
            ['%s', '%d']
        );

        if ($result !== false) {
            do_action('wpwps_log_info', 'Failed jobs reset for retry', [
                'jobs_reset' => $result
            ]);
        }
    }

    private function processJob(\stdClass $job): void {
        $data = maybe_unserialize($job->data);

        switch ($job->job_type) {
            case 'sync_product':
                $product_service = new ProductService();
                $product_service->syncProduct($data);
                break;

            case 'sync_order':
                $order_service = new OrderService();
                $order_service->syncOrder($data);
                break;

            case 'send_email':
                $email_service = new EmailService();
                $email_service->send($data['to'], $data['subject'], $data['message'], $data['headers'] ?? [], $data['attachments'] ?? []);
                break;

            case 'sync_shipping':
                $shipping_service = new ShippingService();
                $shipping_service->syncShippingProfiles();
                break;

            case 'cleanup_logs':
                $this->logger_service->cleanupOldLogs($data['days'] ?? 30);
                break;

            default:
                throw new \Exception('Unknown job type: ' . $job->job_type);
        }
    }

    public function createJobQueueTable(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wpwps_job_queue';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            job_type varchar(50) NOT NULL,
            data longtext,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts int(11) NOT NULL DEFAULT 0,
            last_error text,
            created_at datetime NOT NULL,
            scheduled_for datetime NOT NULL,
            completed_at datetime,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY scheduled_for (scheduled_for)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function getQueueStats(): array {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwps_job_queue';

        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as last_24h
            FROM $table_name
        ");

        return [
            'total' => (int) $stats->total,
            'pending' => (int) $stats->pending,
            'completed' => (int) $stats->completed,
            'failed' => (int) $stats->failed,
            'last_24h' => (int) $stats->last_24h
        ];
    }
}