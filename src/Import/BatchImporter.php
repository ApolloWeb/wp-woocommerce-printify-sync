<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Import;

class BatchImporter {
    const IMPORT_HOOK = 'wpwps_import_product_batch';
    const BATCH_SIZE = 5;

    public function scheduleImport($product_ids, $shop_id) {
        // Split products into batches
        $batches = array_chunk($product_ids, self::BATCH_SIZE);
        
        foreach ($batches as $index => $batch) {
            as_schedule_single_action(
                time() + ($index * 60), // Space out batches by 1 minute
                self::IMPORT_HOOK,
                [
                    'product_ids' => $batch,
                    'shop_id' => $shop_id,
                    'batch' => $index + 1,
                    'total_batches' => count($batches)
                ],
                'printify-import'
            );
        }

        return count($batches);
    }

    public function processBatch($product_ids, $shop_id, $batch, $total_batches) {
        $api = new PrintifyAPI();
        $importer = new ProductImporter();
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($product_ids as $product_id) {
            try {
                $data = $api->getProduct($product_id, $shop_id);
                if (empty($data)) {
                    throw new \Exception('No product data received');
                }

                $wc_product_id = $importer->importProduct($data);
                $results['success'][] = $product_id;

            } catch (\Exception $e) {
                error_log("Failed to import product {$product_id}: " . $e->getMessage());
                $results['failed'][] = $product_id;
            }
        }

        // Update import status
        $this->updateImportStatus($batch, $total_batches, $results);

        return $results;
    }

    private function updateImportStatus($batch, $total_batches, $results) {
        $status = get_option('wpwps_import_status', []);
        $status['current_batch'] = $batch;
        $status['total_batches'] = $total_batches;
        $status['processed'] = ($status['processed'] ?? 0) + count($results['success']);
        $status['failed'] = ($status['failed'] ?? 0) + count($results['failed']);
        $status['last_updated'] = current_time('mysql');
        
        update_option('wpwps_import_status', $status);
    }

    public function triggerManualImport() {
        try {
            // Clear any existing import status
            delete_option('wpwps_import_status');
            
            // Get API credentials
            $api_key = get_option('wpwps_printify_api_key');
            $shop_id = get_option('wpwps_printify_shop_id');

            if (!$api_key || !$shop_id) {
                throw new \Exception('API credentials not configured');
            }

            $api = new PrintifyAPI();
            $page = 1;
            $limit = 100;
            $all_product_ids = [];

            do {
                $products = $api->getProducts($shop_id, [
                    'page' => $page,
                    'limit' => $limit
                ]);

                if (empty($products['data'])) {
                    break;
                }

                $product_ids = array_column($products['data'], 'id');
                $all_product_ids = array_merge($all_product_ids, $product_ids);
                
                $page++;
            } while (!empty($products['data']));

            if (empty($all_product_ids)) {
                throw new \Exception('No products found to import');
            }

            // Schedule import in batches
            $batches = $this->scheduleImport($all_product_ids, $shop_id);

            return [
                'success' => true,
                'message' => sprintf(
                    __('Scheduled import of %d products in %d batches', 'wp-woocommerce-printify-sync'),
                    count($all_product_ids),
                    $batches
                )
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
