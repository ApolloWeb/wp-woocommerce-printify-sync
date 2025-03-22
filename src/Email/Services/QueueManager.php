<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Services;

class QueueManager {
    private $queue_table;
    private $logger;

    public function init() {
        add_action('wp_ajax_wpwps_retry_email', [$this, 'retryEmail']);
        add_action('wp_ajax_wpwps_delete_email', [$this, 'deleteEmail']);
        add_action('wp_ajax_wpwps_bulk_retry', [$this, 'bulkRetry']);
    }

    public function retryEmail($email_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->queue_table,
            [
                'status' => 'pending',
                'attempts' => 0,
                'retry_after' => null,
                'last_error' => null
            ],
            ['id' => $email_id]
        );
    }

    public function getQueuedEmails($status = null, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $where = $status ? $wpdb->prepare('WHERE status = %s', $status) : '';
        
        return $wpdb->get_results(
            "SELECT * FROM {$this->queue_table} 
            {$where}
            ORDER BY created_at DESC
            LIMIT {$offset}, {$limit}"
        );
    }
}
