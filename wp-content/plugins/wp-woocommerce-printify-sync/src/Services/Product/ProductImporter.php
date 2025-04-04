<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Product;

class ProductImporter {
    private $batch_size = 10;

    public function scheduleImport(array $products) {
        foreach (array_chunk($products, $this->batch_size) as $batch) {
            as_enqueue_async_action(
                'wpwps_process_products_batch',
                ['products' => $batch],
                'product-import'
            );
        }
    }

    public function processBatch(array $products) {
        foreach ($products as $product) {
            // Schedule individual product processing
            as_enqueue_async_action(
                'wpwps_process_single_product',
                ['product_data' => $product],
                'product-import'
            );
        }
    }

    public function processSingleProduct(array $product_data) {
        try {
            // Create/update product
            $product_id = $this->createOrUpdateProduct($product_data);

            // Schedule category & tag processing
            as_enqueue_async_action(
                'wpwps_process_product_terms',
                [
                    'product_id' => $product_id,
                    'categories' => $product_data['categories'] ?? [],
                    'tags' => $product_data['tags'] ?? []
                ],
                'product-import'
            );

            // Schedule variants processing
            if (!empty($product_data['variants'])) {
                as_enqueue_async_action(
                    'wpwps_process_product_variants',
                    [
                        'product_id' => $product_id,
                        'variants' => $product_data['variants']
                    ],
                    'product-import'
                );
            }

            // Schedule images processing
            if (!empty($product_data['images'])) {
                as_enqueue_async_action(
                    'wpwps_process_product_images',
                    [
                        'product_id' => $product_id,
                        'images' => $product_data['images']
                    ],
                    'product-import'
                );
            }

        } catch (\Exception $e) {
            do_action('wpwps_sync_error', $e, $product_data);
        }
    }

    private function createOrUpdateProduct(array $data): int {
        $product = new \WC_Product_Variable();
        
        if (!empty($data['id'])) {
            $product = wc_get_product($data['id']);
        }

        $product->set_name($data['title']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_description($data['description']);
        $product->set_short_description($data['short_description'] ?? '');
        
        // Set retail price meta
        $product->update_meta_data('_retail_price_source', 'printify');
        
        return $product->save();
    }
}
