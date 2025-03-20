<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Import;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Import\ProductMetaHelper;

class ProductImporter
{
    /**
     * The API object
     * 
     * @var PrintifyAPI
     */
    private PrintifyAPI $api;
    
    /**
     * The settings object
     * 
     * @var Settings
     */
    private Settings $settings;
    
    /**
     * Batch size for processing
     * 
     * @var int
     */
    private int $batchSize = 5;
    
    /**
     * @var PriceConverter
     */
    private PriceConverter $priceConverter;
    
    /**
     * Constructor
     * 
     * @param PrintifyAPI $api
     * @param Settings $settings
     * @param PriceConverter|null $priceConverter
     */
    public function __construct(PrintifyAPI $api, Settings $settings, ?PriceConverter $priceConverter = null)
    {
        $this->api = $api;
        $this->settings = $settings;
        $this->priceConverter = $priceConverter ?? new PriceConverter();
        
        // Register Action Scheduler hooks
        add_action('wpwps_start_product_import', [$this, 'startImport'], 10, 3);
        add_action('wpwps_process_product_import_queue', [$this, 'processImportQueue'], 10, 1);
    }
    
    /**
     * Start the import process
     * 
     * @param string $shopId
     * @param string $productType
     * @param string $syncMode
     */
    public function startImport(string $shopId, string $productType = '', string $syncMode = 'all'): void
    {
        try {
            // Check if we have products in transient
            $products = get_transient('wpwps_retrieved_products');
            
            if (false === $products) {
                // No products in transient, fetch them from API
                $products = $this->api->getProducts($shopId);
                
                if (is_wp_error($products)) {
                    throw new \Exception($products->get_error_message());
                }
                
                // Filter products if needed
                if (!empty($productType)) {
                    $products = array_filter($products, function($product) use ($productType) {
                        return isset($product['type']) && $product['type'] === $productType;
                    });
                }
            } else {
                // Products found in transient, clean up the transient
                delete_transient('wpwps_retrieved_products');
                delete_transient('wpwps_import_sync_mode');
            }
            
            // Update import stats
            $importStats = get_option('wpwps_import_stats', [
                'total' => 0,
                'processed' => 0,
                'imported' => 0,
                'updated' => 0,
                'failed' => 0,
            ]);
            
            $importStats['total'] = count($products);
            update_option('wpwps_import_stats', $importStats);
            
            // Queue up the products for processing
            $this->queueProductsForImport($products, $shopId, $syncMode);
            
            // Record the start time
            update_option('wpwps_last_import_timestamp', time());
            
        } catch (\Exception $e) {
            // Log the error
            error_log('Printify product import error: ' . $e->getMessage());
        }
    }
    
    /**
     * Queue products for import
     * 
     * @param array $products
     * @param string $shopId
     * @param string $syncMode
     */
    private function queueProductsForImport(array $products, string $shopId, string $syncMode): void
    {
        // Create batches of product IDs for processing
        $batches = array_chunk($products, $this->batchSize);
        
        foreach ($batches as $index => $batch) {
            $productIds = array_map(function($product) {
                return $product['id'];
            }, $batch);
            
            as_schedule_single_action(
                time() + ($index * 10), // Stagger the processing to avoid overwhelming the server
                'wpwps_process_product_import_queue',
                [
                    'shop_id' => $shopId,
                    'product_ids' => $productIds,
                    'sync_mode' => $syncMode,
                ]
            );
        }
    }
    
    /**
     * Process a batch of products from the import queue
     * 
     * @param array $args
     */
    public function processImportQueue(array $args): void
    {
        $shopId = $args['shop_id'];
        $productIds = $args['product_ids'];
        $syncMode = $args['sync_mode'];
        
        $importStats = get_option('wpwps_import_stats');
        
        foreach ($productIds as $printifyProductId) {
            try {
                // Get detailed product data from Printify
                $printifyProduct = $this->api->getProduct($shopId, $printifyProductId);
                
                if (is_wp_error($printifyProduct)) {
                    throw new \Exception($printifyProduct->get_error_message());
                }
                
                // Check if product already exists in WooCommerce
                $wcProductId = $this->getWooCommerceProductId($printifyProductId);
                
                if ($wcProductId && $syncMode === 'new_only') {
                    // Skip this product as it already exists and we're only importing new products
                    $importStats['processed']++;
                    continue;
                }
                
                // Create or update the WooCommerce product
                if ($wcProductId) {
                    $this->updateWooCommerceProduct($wcProductId, $printifyProduct);
                    ProductMetaHelper::updateSyncStatus($wcProductId, 'success');
                    $importStats['updated']++;
                } else {
                    $newProductId = $this->createWooCommerceProduct($printifyProduct);
                    ProductMetaHelper::updateSyncStatus($newProductId, 'success');
                    $importStats['imported']++;
                }
                
                $importStats['processed']++;
                
            } catch (\Exception $e) {
                // Log the error
                error_log('Error importing Printify product ' . $printifyProductId . ': ' . $e->getMessage());
                
                // If the product exists, mark it as failed
                $wcProductId = $this->getWooCommerceProductId($printifyProductId);
                if ($wcProductId) {
                    ProductMetaHelper::updateSyncStatus($wcProductId, 'failed');
                }
                
                $importStats['failed']++;
                $importStats['processed']++;
            }
            
            // Update import stats
            update_option('wpwps_import_stats', $importStats);
        }
        
        // Check if all products have been processed
        if ($importStats['processed'] >= $importStats['total']) {
            // Log completion
            update_option('wpwps_last_import_completed', time());
        }
    }
    
    /**
     * Check if a Printify product exists in WooCommerce
     * 
     * @param string $printifyProductId
     * @return int|false
     */
    private function getWooCommerceProductId(string $printifyProductId)
    {
        return ProductMetaHelper::findProductByPrintifyId($printifyProductId);
    }
    
    /**
     * Create a new WooCommerce product from Printify data
     * 
     * @param array $printifyProduct
     * @return int WooCommerce product ID
     */
    private function createWooCommerceProduct(array $printifyProduct): int
    {
        // Create the main product (parent of variations)
        $product = new \WC_Product_Variable();
        
        // Set basic product data
        $product->set_name($printifyProduct['title']);
        $product->set_description($printifyProduct['description']);
        $product->set_status('publish');
        
        // Set product meta to track Printify relationship using the helper
        ProductMetaHelper::updatePrintifyMeta($product, $printifyProduct);
        
        // Set product tags if any
        if (!empty($printifyProduct['tags'])) {
            $this->setProductTags($product, $printifyProduct['tags']);
        }
        
        // Set product categories based on blueprint_id
        if (!empty($printifyProduct['blueprint_id'])) {
            $this->setProductCategories($product, $printifyProduct['blueprint_id']);
        }
        
        // Save the product
        $product->save();
        $productId = $product->get_id();
        
        // Process and add all product images
        $this->importProductImages($productId, $printifyProduct);
        
        // Process variants and create variations
        $this->processProductVariants($productId, $printifyProduct);
        
        return $productId;
    }
    
    /**
     * Update an existing WooCommerce product with new Printify data
     * 
     * @param int $productId
     * @param array $printifyProduct
     * @return int
     */
    private function updateWooCommerceProduct(int $productId, array $printifyProduct): int
    {
        // Get the existing product
        $product = wc_get_product($productId);
        
        if (!$product) {
            throw new \Exception('WooCommerce product not found: ' . $productId);
        }
        
        // Update basic product data
        $product->set_name($printifyProduct['title']);
        $product->set_description($printifyProduct['description']);
        
        // Update product meta
        $product->update_meta_data('_printify_provider_id', $printifyProduct['print_provider']['id'] ?? '');
        $product->update_meta_data('_printify_last_synced', current_time('mysql'));
        
        // Update tags if any
        if (!empty($printifyProduct['tags'])) {
            $this->setProductTags($product, $printifyProduct['tags']);
        }
        
        // Update categories if needed
        if (!empty($printifyProduct['blueprint_id'])) {
            $this->setProductCategories($product, $printifyProduct['blueprint_id']);
        }
        
        // Save the product
        $product->save();
        
        // Process and update product images
        $this->importProductImages($productId, $printifyProduct);
        
        // Process variants and update variations
        $this->processProductVariants($productId, $printifyProduct);
        
        return $productId;
    }
    
    /**
     * Import product images from Printify
     * 
     * @param int $productId
     * @param array $printifyProduct
     */
    private function importProductImages(int $productId, array $printifyProduct): void
    {
        if (empty($printifyProduct['images'])) {
            return;
        }
        
        $product = wc_get_product($productId);
        
        // Get existing images to avoid duplicate imports
        $existingImages = $product->get_gallery_image_ids();
        $mainImageId = $product->get_image_id();
        
        if ($mainImageId) {
            $existingImages[] = $mainImageId;
        }
        
        $newImageIds = [];
        $isFirstImage = empty($mainImageId);
        
        foreach ($printifyProduct['images'] as $index => $image) {
            $imageUrl = $image['src'];
            $imageId = $this->importExternalImage($imageUrl, $productId);
            
            if ($imageId) {
                // The first image becomes the product thumbnail unless we already have one
                if ($isFirstImage) {
                    $product->set_image_id($imageId);
                    $isFirstImage = false;
                } else {
                    $newImageIds[] = $imageId;
                }
            }
        }
        
        // Set gallery images if we have any new ones
        if (!empty($newImageIds)) {
            $product->set_gallery_image_ids($newImageIds);
        }
        
        $product->save();
    }
    
    /**
     * Import an external image and attach it to a product
     * 
     * @param string $imageUrl
     * @param int $productId
     * @return int|false
     */
    private function importExternalImage(string $imageUrl, int $productId)
    {
        // Check if image already exists by URL
        $existingAttachment = $this->getAttachmentByUrl($imageUrl);
        if ($existingAttachment) {
            return $existingAttachment;
        }
        
        // Make sure required file is included for media_sideload_image()
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Download the image
        $attachmentId = media_sideload_image($imageUrl, $productId, '', 'id');
        
        if (is_wp_error($attachmentId)) {
            error_log('Error importing image: ' . $attachmentId->get_error_message());
            return false;
        }
        
        // Store the source URL as meta to avoid re-downloading
        update_post_meta($attachmentId, '_printify_source_url', $imageUrl);
        
        return $attachmentId;
    }
    
    /**
     * Get attachment ID by source URL
     * 
     * @param string $url
     * @return int|false
     */
    private function getAttachmentByUrl(string $url)
    {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_source_url' 
            AND meta_value = %s 
            LIMIT 1",
            $url
        );
        
        $result = $wpdb->get_var($query);
        
        return $result ? (int) $result : false;
    }
    
    /**
     * Process product variants and create WooCommerce variations
     * 
     * @param int $productId
     * @param array $printifyProduct
     */
    private function processProductVariants(int $productId, array $printifyProduct): void
    {
        if (empty($printifyProduct['variants'])) {
            return;
        }
        
        $product = wc_get_product($productId);
        
        // Collect all attributes from variants
        $attributes = $this->extractVariantAttributes($printifyProduct['variants']);
        
        // Set product attributes
        $this->setProductAttributes($product, $attributes);
        
        // Save the product to ensure attributes are created
        $product->save();
        
        // Create variants as product variations
        $variantIds = [];
        
        foreach ($printifyProduct['variants'] as $variant) {
            $variantIds[] = $variant['id'];
            $this->createOrUpdateVariation($productId, $variant, $attributes);
        }
        
        // Store variant IDs in the parent product
        $product->update_meta_data('_printify_variant_ids', $variantIds);
        $product->save();
    }
    
    /**
     * Extract attributes from variants
     * 
     * @param array $variants
     * @return array
     */
    private function extractVariantAttributes(array $variants): array
    {
        $attributes = [];
        
        foreach ($variants as $variant) {
            foreach ($variant['options'] as $key => $value) {
                $attributeName = wc_sanitize_taxonomy_name($key);
                if (!isset($attributes[$attributeName])) {
                    $attributes[$attributeName] = [
                        'name' => $key,
                        'values' => []
                    ];
                }
                
                if (!in_array($value, $attributes[$attributeName]['values'])) {
                    $attributes[$attributeName]['values'][] = $value;
                }
            }
        }
        
        return $attributes;
    }
    
    /**
     * Set product attributes
     * 
     * @param \WC_Product $product
     * @param array $attributes
     */
    private function setProductAttributes(\WC_Product $product, array $attributes): void
    {
        $productAttributes = [];
        
        foreach ($attributes as $attributeKey => $attribute) {
            // Create the attribute
            $productAttribute = new \WC_Product_Attribute();
            $productAttribute->set_name($attribute['name']);
            $productAttribute->set_options($attribute['values']);
            $productAttribute->set_position(0);
            $productAttribute->set_visible(true);
            $productAttribute->set_variation(true);
            
            $productAttributes[] = $productAttribute;
        }
        
        $product->set_attributes($productAttributes);
        $product->save();
    }
    
    /**
     * Create or update a WooCommerce product variation
     * 
     * @param int $productId
     * @param array $variant
     * @param array $attributes
     * @return int
     */
    private function createOrUpdateVariation(int $productId, array $variant, array $attributes): int
    {
        // Check if variation exists
        $variationId = $this->getVariationIdByPrintifyVariantId($productId, $variant['id']);
        
        if ($variationId) {
            $variation = wc_get_product($variationId);
            if (!$variation) {
                // Variation exists in meta but product not found
                $variation = new \WC_Product_Variation();
                $variation->set_parent_id($productId);
            }
        } else {
            $variation = new \WC_Product_Variation();
            $variation->set_parent_id($productId);
        }
        
        // Set basic variation data
        $variation->set_status('publish');
        
        // Convert price from cents to decimal for WooCommerce (using price, not cost)
        $price = $this->priceConverter->convertFromMinorUnits($variant['price']);
        $variation->set_price($price);
        $variation->set_regular_price($price);
        
        if (!empty($variant['sku'])) {
            $variation->set_sku($variant['sku']);
        }
        
        // Set stock status based on variant availability
        $isInStock = isset($variant['is_enabled']) ? (bool) $variant['is_enabled'] : true;
        $variation->set_stock_status($isInStock ? 'instock' : 'outofstock');
        
        // Set variation meta data
        $variation->update_meta_data('_printify_variant_id', $variant['id']);
        $variation->update_meta_data('_printify_cost_price', $this->priceConverter->convertFromMinorUnits($variant['cost'] ?? 0));
        
        // Set variation attributes
        $variationAttributes = [];
        
        foreach ($variant['options'] as $key => $value) {
            $attributeName = 'attribute_' . wc_sanitize_taxonomy_name($key);
            $variationAttributes[$attributeName] = $value;
        }
        
        $variation->set_attributes($variationAttributes);
        
        // Save the variation
        $variation->save();
        
        return $variation->get_id();
    }
    
    /**
     * Convert Printify price from cents to decimal
     * 
     * @param int $priceInCents
     * @return float
     */
    private function convertPrintifyPriceToDecimal(int $priceInCents): float
    {
        return floatval($priceInCents) / 100;
    }
    
    /**
     * Get variation ID by Printify variant ID
     *
     * @param int $productId
     * @param string $variantId
     * @return int|false
     */
    private function getVariationIdByPrintifyVariantId(int $productId, string $variantId)
    {
        return ProductMetaHelper::findVariationByPrintifyVariantId($variantId, $productId);
    }
    
    /**
     * Set product tags
     * 
     * @param \WC_Product $product
     * @param array $tags
     */
    private function setProductTags(\WC_Product $product, array $tags): void
    {
        $termIds = [];
        
        foreach ($tags as $tag) {
            $term = get_term_by('name', $tag, 'product_tag');
            
            if (!$term) {
                $term = wp_insert_term($tag, 'product_tag');
                if (!is_wp_error($term)) {
                    $termIds[] = $term['term_id'];
                }
            } else {
                $termIds[] = $term->term_id;
            }
        }
        
        if (!empty($termIds)) {
            wp_set_object_terms($product->get_id(), $termIds, 'product_tag');
        }
    }
    
    /**
     * Set product categories based on blueprint ID
     * 
     * @param \WC_Product $product
     * @param string $blueprintId
     */
    private function setProductCategories(\WC_Product $product, string $blueprintId): void
    {
        // Blueprint mapping to category hierarchy
        $blueprintCategories = [
            // T-shirts
            '1' => ['Apparel', 'T-Shirts'],
            '2' => ['Apparel', 'T-Shirts', 'Premium'],
            // Hoodies
            '3' => ['Apparel', 'Hoodies'],
            '4' => ['Apparel', 'Hoodies', 'Premium'],
            // Mugs
            '10' => ['Home Decor', 'Drinkware', 'Mugs'],
            // Phone cases
            '20' => ['Accessories', 'Phone Cases'],
            // Default
            'default' => ['Printify Products']
        ];
        
        // Get category path based on blueprint or use default
        $categoryPath = $blueprintCategories[$blueprintId] ?? $blueprintCategories['default'];
        
        // Create category hierarchy and get the final term ID
        $parentId = 0;
        $finalTermId = null;
        
        foreach ($categoryPath as $categoryName) {
            $term = term_exists($categoryName, 'product_cat', $parentId);
            
            if (!$term) {
                $term = wp_insert_term($categoryName, 'product_cat', ['parent' => $parentId]);
                if (is_wp_error($term)) {
                    continue;
                }
            }
            
            $parentId = is_array($term) ? $term['term_id'] : $term;
        }
        
        // Set the final term ID as the product category
        if ($parentId) {
            wp_set_object_terms($product->get_id(), [$parentId], 'product_cat');
        }
    }
}