<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class BackgroundProcessor {
    private $action_scheduler;

    public function __construct(ActionScheduler $action_scheduler) {
        $this->action_scheduler = $action_scheduler;
    }

    public function scheduleBulkOperation(string $operation, array $items, array $args = []): void {
        // Split items into chunks
        $chunks = array_chunk($items, 20);
        
        foreach ($chunks as $chunk) {
            $this->action_scheduler->scheduleTask(
                'wpwps_bulk_' . $operation,
                ['items' => $chunk, 'args' => $args],
                time() + 30
            );
        }
    }

    public function processBulkOperation(string $operation, array $items, array $args): void {
        foreach ($items as $item) {
            try {
                $this->processItem($operation, $item, $args);
            } catch (\Exception $e) {
                // Log error but continue processing
                $this->logger->error('Bulk operation error', [
                    'operation' => $operation,
                    'item' => $item,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
