<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Services;

class EmailQueueMonitor {
    private $queue_table;
    private $logger;
    private $notification_threshold = 5;
    private $max_retries = 3;

    public function __construct(Logger $logger) {
        global $wpdb;
        $this->queue_table = $wpdb->prefix . 'wpwps_email_queue';
        $this->logger = $logger;
    }

    public function getQueueStats() {
        global $wpdb;
        
        return [
            'pending' => $this->getCountByStatus('pending'),
            'failed' => $this->getCountByStatus('failed'),
            'retry_pending' => $this->getRetryPendingCount(),
            'processed_24h' => $this->getProcessedCount(24),
            'failure_rate' => $this->getFailureRate()
        ];
    }

    public function handleFailedEmail($email_id, $error) {
        global $wpdb;
        
        $email = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->queue_table} WHERE id = %d",
            $email_id
        ));

        if (!$email) return;

        $retry_after = $this->calculateBackoff($email->attempts);
        
        $wpdb->update(
            $this->queue_table,
            [
                'attempts' => $email->attempts + 1,
                'last_error' => $error,
                'status' => ($email->attempts + 1) >= $this->max_retries ? 'failed' : 'pending',
                'retry_after' => date('Y-m-d H:i:s', time() + $retry_after)
            ],
            ['id' => $email_id]
        );

        if ($email->attempts >= $this->notification_threshold) {
            $this->notifyAdminOfFailures();
        }
    }

    private function calculateBackoff($attempts) {
        // Exponential backoff: 5min, 15min, 45min
        return 300 * pow(3, $attempts - 1);
    }

    private function notifyAdminOfFailures() {
        $stats = $this->getQueueStats();
        if ($stats['failed'] > $this->notification_threshold) {
            wp_mail(
                get_option('admin_email'),
                'Email Queue Alert: High Failure Rate',
                $this->getFailureNotificationMessage($stats)
            );
        }
    }
}
