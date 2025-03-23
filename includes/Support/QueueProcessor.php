<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class QueueProcessor {
    private $logger;
    private $settings;

    public function __construct($logger, $settings) {
        $this->logger = $logger;
        $this->settings = $settings;
    }

    public function init(): void {
        add_action('wpwps_process_queues', [$this, 'processQueues']);
        
        if (!wp_next_scheduled('wpwps_process_queues')) {
            wp_schedule_event(time(), 'every_five_minutes', 'wpwps_process_queues');
        }
    }

    public function processQueues(): void {
        $this->processEmailQueue();
        $this->processApiQueue();
        $this->processSyncQueue();
    }
}
