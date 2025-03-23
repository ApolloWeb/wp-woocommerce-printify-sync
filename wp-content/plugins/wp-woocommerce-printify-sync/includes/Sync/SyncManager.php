<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Sync;

class SyncManager {
    private $api;
    private $logger;
    private $batch_size = 50;
    private $queue = [];

    public function __construct(PrintifyApi $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
    }

    public function queueSync(string $type, array $items): void {
        $batches = array_chunk($items, $this->batch_size);
        foreach ($batches as $batch) {
            wp_schedule_single_event(
                time(),
                'wpps_process_sync_batch',
                ['type' => $type, 'items' => $batch]
            );
        }
    }

    public function processBatch(string $type, array $items): array {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($items as $item) {
            try {
                $this->processItem($type, $item);
                $results['processed']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'item' => $item,
                    'error' => $e->getMessage()
                ];
                $this->logger->log("Sync failed for {$type}: " . $e->getMessage(), 'error');
            }
        }

        return $results;
    }

    private function processItem(string $type, array $item): void {
        switch ($type) {
            case 'products':
                $this->syncProduct($item);
                break;
            case 'orders':
                $this->syncOrder($item);
                break;
            default:
                throw new \InvalidArgumentException("Unknown sync type: {$type}");
        }
    }
}
