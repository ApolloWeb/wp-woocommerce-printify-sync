<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class QueueHandler {
    private $logger;
    private $action_scheduler;
    
    const GROUP = 'wpwps_queue';
    
    public function __construct(Logger $logger, ActionSchedulerService $action_scheduler) {
        $this->logger = $logger;
        $this->action_scheduler = $action_scheduler;
    }

    public function init() {
        add_action('wpwps_process_queue', [$this, 'processQueue']);
        add_action('init', [$this, 'scheduleQueueProcessor']);
    }

    public function addToQueue($task, $data = [], $priority = 10) {
        return $this->action_scheduler->schedule(
            'wpwps_process_queue',
            [
                'task' => $task,
                'data' => $data
            ],
            [
                'group' => self::GROUP,
                'priority' => $priority
            ]
        );
    }

    public function getQueueStatus() {
        return [
            'pending' => $this->action_scheduler->getPendingCount(self::GROUP),
            'running' => $this->action_scheduler->getRunningCount(self::GROUP),
            'failed' => $this->action_scheduler->getFailedCount(self::GROUP)
        ];
    }

    public function processQueue($args) {
        $task = $args['task'] ?? null;
        $data = $args['data'] ?? [];

        if (!$task) {
            $this->logger->error('Invalid queue task');
            return;
        }

        try {
            do_action("wpwps_process_{$task}", $data);
            $this->logger->info(sprintf('Processed queue task: %s', $task));
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Queue task failed: %s - %s', $task, $e->getMessage()));
            throw $e;
        }
    }

    private function scheduleQueueProcessor() {
        if (!wp_next_scheduled('wpwps_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'wpwps_process_queue');
        }
    }
}
