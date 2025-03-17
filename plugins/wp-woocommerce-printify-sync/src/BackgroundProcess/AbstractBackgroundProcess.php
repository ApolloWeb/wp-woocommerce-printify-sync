<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\BackgroundProcess;

use ApolloWeb\WPWooCommercePrintifySync\Notification\NotificationManager;

abstract class AbstractBackgroundProcess extends \WP_Background_Process
{
    protected $action = 'wpwps_background_process';
    protected NotificationManager $notificationManager;

    public function __construct()
    {
        parent::__construct();
        $this->notificationManager = new NotificationManager();
    }

    protected function complete()
    {
        parent::complete();

        $this->notificationManager->sendProcessStatusEmail(
            $this->action,
            'completed',
            [
                'processed_items' => $this->total_items_processed ?? 0,
                'duration' => $this->get_process_duration(),
                'memory_usage' => size_format(memory_get_peak_usage(true))
            ]
        );
    }

    protected function task($item)
    {
        try {
            return $this->process_item($item);
        } catch (\Exception $e) {
            $this->notificationManager->sendErrorAlert(
                "Error processing {$this->action}",
                [
                    'error' => $e->getMessage(),
                    'item' => $item
                ]
            );
            return false;
        }
    }

    private function get_process_duration(): string
    {
        $start_time = get_option("wpwps_{$this->action}_start_time");
        if (!$start_time) {
            return 'Unknown';
        }

        $duration = time() - $start_time;
        return human_time_diff(0, $duration);
    }

    abstract protected function process_item($item);
}