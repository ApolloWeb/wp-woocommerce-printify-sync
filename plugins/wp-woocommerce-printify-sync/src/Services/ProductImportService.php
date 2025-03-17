<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\ProductImporterInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\ImageHandlerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\APIClientInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class ProductImportService extends AbstractService implements ProductImporterInterface
{
    private APIClientInterface $api;
    private ImageHandlerInterface $imageHandler;
    private int $batchSize;

    public function __construct(
        APIClientInterface $api,
        ImageHandlerInterface $imageHandler,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        parent::__construct($logger, $config);
        $this->api = $api;
        $this->imageHandler = $imageHandler;
        $this->batchSize = (int)$this->config->get('import_batch_size', 20);
    }

    public function importProducts(array $products): array
    {
        $results = [];
        $chunks = array_chunk($products, $this->batchSize);

        foreach ($chunks as $chunk) {
            if ($this->api->isRateLimited()) {
                $this->handleRateLimit();
            }

            foreach ($chunk as $product) {
                try {
                    $productId = $this->createProduct($product);
                    if ($productId) {
                        $results['success'][] = $productId;
                        $this->logOperation('importProducts', [
                            'message' => 'Product imported successfully',
                            'product_id' => $productId,
                            'printify_id' => $product['id']
                        ]);
                    }
                } catch (\Exception $e) {
                    $results['failed'][] = $product['id'];
                    $this->logError('importProducts', $e, [
                        'printify_id' => $product['id']
                    ]);
                }
            }

            // Add small delay between chunks to prevent rate limiting
            if (next($chunks) !== false) {
                sleep(1);
            }
        }

        return $results;
    }

    public function updateProduct(int $productId, array $data): bool
    {
        try {
            $post = [
                'ID' => $productId,
                'post_title' => $data['title'],
                'post_content' => $data['description'],
                'post_status' => 'publish',
            ];

            wp_update_post($post);

            // Update meta fields
            $this->updateProductMeta($productId, $data);

            // Update images
            if (!empty($data['images'])) {
                $this->handleProductImages($productId, $data['images']);
            }

            // Update variants
            if (!empty($data['variants'])) {
                $this->syncVariants($productId, $data['variants']);
            }

            $this->logOperation('updateProduct', [
                'message' => 'Product updated successfully',
                'product_id' => $productId
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logError('updateProduct', $e, [
                'product_id' => $productId
            ]);
            return false;
        }
    }

    public function deleteProduct(int $productId): bool
    {
        try {
            wp_delete_post($productId, true);

            $this->logOperation('deleteProduct', [
                'message' => 'Product deleted successfully',
                'product_id' => $productId
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logError('deleteProduct', $e, [
                'product_id' => $productId
            ]);
            return false;
        }
    }

    public function syncVariants(int $productId, array $variants): bool
    {
        try {
            $product = wc_get_product($productId);
            if (!$product) {
                throw new \Exception('Product not found');
            }

            // Clear existing variations
            $existingVariations = $product->get_children();
            foreach ($existingVariations as $variationId) {
                wp_delete_post($variationId, true);
            }

            // Create new variations
            foreach ($variants as $variant) {
                $variation = new \WC_Product_Variation();
                $variation->set_parent_id($productId);
                
                // Set variant data
                $variation->set_regular_price($variant['price']);
                if (isset($variant['sale_price'])) {
                    $variation->set_sale_price($variant['sale_price']);
                }

                // Set attributes
                $attributes = [];
                foreach ($variant['options'] as $key => $value) {
                    $attributes["pa_$key"] = $value;
                }
                $variation->set_attributes($attributes);

                // Set SKU and stock
                $variation->set_sku($variant['sku']);
                $variation->set_stock_quantity($variant['quantity']);
                $variation->set_manage_stock(true);

                $variation->save();
            }

            $this->logOperation('syncVariants', [
                'message' => 'Variants synced successfully',
                'product_id' => $productId,
                'variant_count' => count($variants)
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logError('syncVariants', $e, [
                'product_id' => $productId
            ]);
            return false;
        }
    }

    private function createProduct(array $data): ?int
    {
        $post = [
            'post_title' => $data['title'],
            'post_content' => $data['description'],
            'post_status' => 'publish',
            'post_type' => 'product'
        ];

        $productId = wp_insert_post($post);
        if (is_wp_error($productId)) {
            throw new \Exception($productId->get_error_message());
        }

        // Set product type
        wp_set_object_terms($productId, 'variable', 'product_type');

        // Update meta fields
        $this->updateProductMeta($productId, $data);

        // Handle images
        if (!empty($data['images'])) {
            $this->handleProductImages($productId, $data['images']);
        }

        // Handle variants
        if (!empty($data['variants'])) {
            $this->syncVariants($productId, $data['variants']);
        }

        return $productId;
    }

    private function updateProductMeta(int $productId, array $data): void
    {
        update_post_meta($productId, '_printify_id', $data['id']);
        update_post_meta($productId, '_printify_shop_id', $data['shop_id']);
        update_post_meta($productId, '_printify_blueprint_id', $data['blueprint_id']);
        update_post_meta($productId, '_printify_provider_id', $data['provider_id']);
        update_post_meta($productId, '_printify_last_sync', $this->getCurrentTime());
    }

    private function handleProductImages(int $productId, array $images): void
    {
        $tempFiles = [];
        $attachmentIds = [];

        try {
            foreach ($images as $index => $image) {
                // Download image
                $tempFile = $this->imageHandler->downloadImage($image['src']);
                if (!$tempFile) {
                    continue;
                }
                $tempFiles[] = $tempFile;

                // Optimize image
                $optimizedFile = $this->imageHandler->optimizeImage($tempFile);
                if ($optimizedFile) {
                    $tempFiles[] = $optimizedFile;
                }

                // Upload to WordPress
                $attachmentId = $this->imageHandler->uploadToWordPress(
                    $optimizedFile ?? $tempFile,
                    $productId
                );

                if ($attachmentId) {
                    $attachmentIds[] = $attachmentId;
                    
                    // Set featured image
                    if ($index === 0) {
                        set_post_thumbnail($productId, $attachmentId);
                    }

                    // Offload to cloud if configured
                    if ($this->config->get('use_cloud_storage', false)) {
                        $this->imageHandler->offloadToCloud(get_attached_file($attachmentId));
                    }
                }
            }

            // Update product gallery
            update_post_meta($productId, '_product_image_gallery', implode(',', array_slice($attachmentIds, 1)));

        } finally {
            // Cleanup temporary files
            $this->imageHandler->cleanupTempFiles($tempFiles);
        }
    }

    private function handleRateLimit(): void
    {
        $limits = $this->api->getRateLimit();
        $waitTime = $limits['reset'] - time();
        if ($waitTime > 0) {
            $this->logOperation('handleRateLimit', [
                'message' => "Rate limit reached. Waiting {$waitTime} seconds",
                'reset_time' => $limits['reset']
            ]);
            sleep($waitTime);
        }
    }
}