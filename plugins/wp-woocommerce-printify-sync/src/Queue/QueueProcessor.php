<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Queue;

class QueueProcessor
{
    private $queues = [];

    public function register(string $queueName, callable $processor): void
    {
        $this->queues[$queueName] = $processor;
    }

    public function processQueue(string $queueName, array $items): void
    {
        if (!isset($this->queues[$queueName])) {
            throw new \InvalidArgumentException("Unknown queue: {$queueName}");
        }

        $processor = $this->queues[$queueName];

        foreach ($items as $item) {
            try {
                $processor($item);
            } catch (\Exception $e) {
                error_log("Queue processing error ({$queueName}): " . $e->getMessage());
                
                // Log the error
                do_action('wpwps_log_error', $e->getMessage(), [
                    'queue' => $queueName,
                    'item' => $item
                ]);
            }
        }
    }
}