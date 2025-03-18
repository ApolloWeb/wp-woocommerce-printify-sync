<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Product;

class ProductImportQueue {
    protected int $chunkSize = 10;

    /**
     * Schedule product import tasks in chunks.
     *
     * @param array $products Array of product data.
     */
    public function scheduleImport(array $products): void {
        $chunks = array_chunk($products, $this->chunkSize);
        foreach ($chunks as $chunk) {
            as_enqueue_async_action('process_product_chunk', ['products' => $chunk]);
        }
    }

    /**
     * Process a chunk of products.
     *
     * @param array $args Contains a 'products' key.
     */
    public static function processChunk(array $args): void {
        if (empty($args['products'])) {
            return;
        }
        $products = $args['products'];
        foreach ($products as $productData) {
            $sku = $productData['printify_id'] ?? '';
            if (empty($sku)) {
                continue;
            }
            $existingProductId = wc_get_product_id_by_sku($sku);
            if ($existingProductId) {
                // ...update product...
            } else {
                // ...insert new variable product...
            }
            // Save additional meta and map attributes.
            // e.g., update_post_meta($productId, 'provider_id', $productData['provider_id']);
            // ...additional processing...
        }
        // Optionally update progress indicator.
    }
}
