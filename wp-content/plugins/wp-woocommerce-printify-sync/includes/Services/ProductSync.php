<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ProductSync {
    private $api;
    private $logger;

    public function __construct(PrintifyApi $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
        
        add_action('wpps_sync_product', [$this, 'syncProduct'], 10, 2);
        add_action('wpps_batch_sync_products', [$this, 'batchSync']);
    }

    public function scheduleSync(): void {
        if (!as_next_scheduled_action('wpps_batch_sync_products')) {
            as_schedule_recurring_action(
                time(),
                DAY_IN_SECONDS,
                'wpps_batch_sync_products'
            );
        }
    }

    public function batchSync(): void {
        $products = $this->api->getProducts();
        foreach ($products as $product) {
            as_enqueue_async_action('wpps_sync_product', [$product['id']], 'printify');
        }
    }

    public function syncProduct(string $printify_id, bool $update_variations = true): void {
        try {
            $data = $this->api->getProduct($printify_id);
            $product = new \ApolloWeb\WPWooCommercePrintifySync\Models\PrintifyProduct($data);
            
            // Create or update product
            $wc_product_id = $this->findOrCreateProduct($product);
            
            if ($update_variations) {
                $this->syncVariations($wc_product_id, $product);
            }

            $this->logger->log("Synced product {$printify_id}");
        } catch (\Exception $e) {
            $this->logger->log($e->getMessage(), 'error');
        }
    }

    private function findOrCreateProduct($product): int {
        $existing_id = $this->findExistingProduct($product->getId());
        if ($existing_id) {
            return $this->updateProduct($existing_id, $product);
        }
        return $this->createProduct($product);
    }

    private function createProduct($product): int {
        $post_data = $product->mapToWooCommerce();
        $product_id = wp_insert_post($post_data);

        if (is_wp_error($product_id)) {
            throw new \Exception("Failed to create product: " . $product_id->get_error_message());
        }

        $this->updateProductMeta($product_id, $product);
        $this->updateProductImages($product_id, $product);
        
        return $product_id;
    }

    private function updateProduct(int $product_id, $product): int {
        $post_data = array_merge(
            $product->mapToWooCommerce(),
            ['ID' => $product_id]
        );
        
        $updated = wp_update_post($post_data);
        if (is_wp_error($updated)) {
            throw new \Exception("Failed to update product: " . $updated->get_error_message());
        }

        $this->updateProductMeta($product_id, $product);
        $this->updateProductImages($product_id, $product);

        return $product_id;
    }

    private function syncVariations(int $product_id, $product): void {
        $variations = $product->getVariants();
        $existing_variations = $this->getExistingVariations($product_id);

        foreach ($variations as $variant) {
            $variation_id = $this->findOrCreateVariation($product_id, $variant, $existing_variations);
            $this->updateVariationMeta($variation_id, $variant);
        }

        // Remove obsolete variations
        $this->cleanupVariations($product_id, array_column($variations, 'id'));
    }

    private function updateProductMeta(int $product_id, $product): void {
        $meta = [
            '_printify_product_id' => $product->getId(),
            '_printify_blueprint_id' => $product->getBlueprintId(),
            '_printify_provider_id' => $product->getProviderId(),
            '_printify_last_synced' => current_time('mysql'),
            '_price' => $product->getRetailPrice(),
            '_regular_price' => $product->getRetailPrice(),
            '_sku' => $product->getSku()
        ];

        foreach ($meta as $key => $value) {
            update_post_meta($product_id, $key, $value);
        }
    }

    private function cleanupVariations(int $product_id, array $valid_variation_ids): void {
        global $wpdb;
        $table = $wpdb->prefix . 'posts';
        
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$table} 
            WHERE post_parent = %d 
            AND post_type = 'product_variation'
            AND ID NOT IN (" . implode(',', array_map('intval', $valid_variation_ids)) . ")",
            $product_id
        ));
    }

    private function retryWithBackoff(callable $callback, int $max_attempts = 3): mixed {
        $attempt = 1;
        $delay = 1;

        while ($attempt <= $max_attempts) {
            try {
                return $callback();
            } catch (\Exception $e) {
                if ($attempt === $max_attempts) {
                    throw $e;
                }
                sleep($delay);
                $delay *= 2;
                $attempt++;
            }
        }
    }
}
