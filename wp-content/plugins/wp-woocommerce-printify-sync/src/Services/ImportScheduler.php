<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImportScheduler {
    const PRODUCT_IMPORT_ACTION = 'wpwps_import_product_batch';
    const IMAGE_IMPORT_ACTION = 'wpwps_import_product_images';
    const BATCH_SIZE = 10;

    private $action_scheduler;
    private $logger;

    public function __construct(ActionSchedulerService $action_scheduler, Logger $logger) {
        $this->action_scheduler = $action_scheduler;
        $this->logger = $logger;
    }

    public function init() {
        add_action(self::PRODUCT_IMPORT_ACTION, [$this, 'processProductBatch']);
        add_action(self::IMAGE_IMPORT_ACTION, [$this, 'processImageImport']);
    }

    public function scheduleProductImport(array $product_ids, $shop_id) {
        $batches = array_chunk($product_ids, self::BATCH_SIZE);
        
        foreach ($batches as $index => $batch) {
            $this->action_scheduler->schedule(
                self::PRODUCT_IMPORT_ACTION,
                [
                    'product_ids' => $batch,
                    'shop_id' => $shop_id,
                    'batch' => $index + 1,
                    'total_batches' => count($batches)
                ],
                ['group' => 'product-import']
            );
        }

        $this->logger->info(sprintf('Scheduled import of %d products in %d batches', 
            count($product_ids), 
            count($batches)
        ));
    }

    public function processProductBatch($args) {
        $product_ids = $args['product_ids'] ?? [];
        $shop_id = $args['shop_id'] ?? null;
        
        if (empty($product_ids) || !$shop_id) {
            throw new \Exception('Invalid import batch data');
        }

        foreach ($product_ids as $product_id) {
            try {
                // Process product import
                do_action('wpwps_process_product_import', $product_id, $shop_id);
                
                // Schedule image import
                $this->scheduleImageImport($product_id);
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Product import failed: %s', $e->getMessage()));
            }
        }
    }

    private function scheduleImageImport($product_id) {
        return $this->action_scheduler->schedule(
            self::IMAGE_IMPORT_ACTION,
            ['product_id' => $product_id],
            [
                'group' => 'image-import',
                'priority' => 5
            ]
        );
    }
}
