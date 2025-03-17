<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Performance;

class BatchProcessor
{
    private $batch_size = 50;
    private $current_batch = 0;
    private $total_items = 0;
    private $processed_items = 0;

    public function processBatch(array $items, callable $processor): array
    {
        $this->total_items = count($items);
        $results = [];
        $errors = [];

        foreach (array_chunk($items, $this->batch_size) as $batch) {
            $this->current_batch++;
            
            try {
                $batch_results = $processor($batch);
                $results = array_merge($results, $batch_results);
                $this->processed_items += count($batch);
                
                // Allow some breathing room between batches
                if ($this->current_batch % 5 === 0) {
                    sleep(1);
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'batch' => $this->current_batch,
                    'error' => $e->getMessage(),
                    'items' => $batch,
                ];
            }

            // Update progress
            $this->updateProgress();
        }

        return [
            'results' => $results,
            'errors' => $errors,
            'stats' => $this->getStats(),
        ];
    }

    private function updateProgress(): void
    {
        $progress = ($this->processed_items / $this->total_items) * 100;
        
        update_option('wpwps_batch_progress', [
            'total' => $this->total_items,
            'processed' => $this->processed_items,
            'progress' => round($progress, 2),
            'current_batch' => $this->current_batch,
            'updated_at' => current_time('mysql'),
        ]);
    }

    private function getStats(): array
    {
        return [
            'total_items' => $this->total_items,
            'processed_items' => $this->processed_items,
            'total_batches' => ceil($this->total_items / $this->batch_size),
            'completed_batches' => $this->current_batch,
            'batch_size' => $this->batch_size,
        ];
    }
}