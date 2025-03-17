<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Progress;

class ProgressTracker
{
    private const PROGRESS_OPTION = 'wpwps_progress_tracking';

    public function startProgress(string $task_id, int $total_items): void
    {
        $progress = [
            'task_id' => $task_id,
            'total' => $total_items,
            'completed' => 0,
            'failed' => 0,
            'start_time' => time(),
            'last_update' => time(),
            'status' => 'running',
            'percentage' => 0,
        ];

        update_option(self::PROGRESS_OPTION . '_' . $task_id, $progress);
    }

    public function updateProgress(string $task_id, int $completed, int $failed = 0): void
    {
        $progress = get_option(self::PROGRESS_OPTION . '_' . $task_id);
        
        if (!$progress) {
            return;
        }

        $progress['completed'] = $completed;
        $progress['failed'] = $failed;
        $progress['last_update'] = time();
        $progress['percentage'] = ($completed / $progress['total']) * 100;

        if ($completed >= $progress['total']) {
            $progress['status'] = 'completed';
        }

        update_option(self::PROGRESS_OPTION . '_' . $task_id, $progress);
    }

    public function getProgress(string $task_id): ?array
    {
        return get_option(self::PROGRESS_OPTION . '_' . $task_id);
    }
}