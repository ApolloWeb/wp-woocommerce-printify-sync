<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce;

use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\ProductImporterInterface;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Handlers\ProductTagsHandler;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Handlers\ProductCategoryHandler;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Handlers\ProductVariantHandler;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Handlers\ProductImageHandler;

class ProductImporter implements ProductImporterInterface
{
    /** @var ProductTagsHandler */
    private $tagsHandler;
    
    /** @var ProductCategoryHandler */
    private $categoryHandler;
    
    /** @var ProductVariantHandler */
    private $variantHandler;
    
    /** @var ProductImageHandler */
    private $imageHandler;
    
    public function __construct() 
    {
        $this->tagsHandler = new ProductTagsHandler();
        $this->categoryHandler = new ProductCategoryHandler();
        $this->variantHandler = new ProductVariantHandler();
        $this->imageHandler = new ProductImageHandler();
    }

    /**
     * {@inheritdoc}
     */
    public function importProduct(array $printifyProduct): int
    {
        // Check if product already exists first
        $existingProductId = $this->getWooProductIdByPrintifyId($printifyProduct['id']);
        
        if ($existingProductId) {
            // Update existing product
            $this->updateProduct($existingProductId, $printifyProduct);
            return $existingProductId;
        }
        
        // Create a new variable product using WC_Product_Variable
        $product = new \WC_Product_Variable();
        $product->set_name($printifyProduct['title']);
        $product->set_description($printifyProduct['description'] ?? '');
        $product->set_short_description($printifyProduct['description'] ? wp_trim_words($printifyProduct['description'], 55) : '');
        $product->set_status(!empty($printifyProduct['visible']) ? 'publish' : 'draft');
        
        // Save the product to get an ID
        $productId = $product->save();
        
        if (!$productId) {
            throw new \Exception('Failed to create product');
        }

        // Save essential Printify metadata
        update_post_meta($productId, '_printify_id', $printifyProduct['id']);
        update_post_meta($productId, '_printify_provider_id', $printifyProduct['provider_id'] ?? '');
        update_post_meta($productId, '_printify_last_updated', current_time('mysql'));
        
        // Handle product tags
        $this->tagsHandler->processProductTags($productId, $printifyProduct);
        
        // Handle product categories based on product_type
        $this->categoryHandler->processCategoriesFromProductType($productId, $printifyProduct);
        
        // Schedule image imports with action scheduler
        $this->imageHandler->scheduleImageImport($productId, $printifyProduct['images'] ?? []);
        
        // Process variants/attributes
        $this->variantHandler->processVariants($productId, $printifyProduct);
        
        // Update sync count
        $this->updateSyncCounter();
        
        return $productId;
    }

    /**
     * {@inheritdoc}
     */
    public function getWooProductIdByPrintifyId(string $printifyId): ?int
    {
        return ProductDataStoreCompatibility::getProductIdByPrintifyId($printifyId);
    }

    /**
     * {@inheritdoc}
     */
    public function updateProduct(int $wooProductId, array $printifyProduct): bool
    {
        try {
            // Basic update of the product
            $product = wc_get_product($wooProductId);
            if (!$product) {
                return false;
            }

            $product->set_name($printifyProduct['title'] ?? '');
            $product->set_description($printifyProduct['description'] ?? '');
            $product->set_short_description($printifyProduct['description'] ? wp_trim_words($printifyProduct['description'], 55) : '');
            $product->set_status(!empty($printifyProduct['visible']) ? 'publish' : 'draft');
            
            // Save the product
            $product->save();

            // Update Printify metadata
            update_post_meta($wooProductId, '_printify_id', $printifyProduct['id']);
            update_post_meta($wooProductId, '_printify_provider_id', $printifyProduct['provider_id'] ?? '');
            update_post_meta($wooProductId, '_printify_last_updated', current_time('mysql'));
            
            // Handle product tags
            $this->tagsHandler->processProductTags($wooProductId, $printifyProduct);
            
            // Handle product categories based on product_type
            $this->categoryHandler->processCategoriesFromProductType($wooProductId, $printifyProduct);
            
            // Schedule image imports with action scheduler
            $this->imageHandler->scheduleImageImport($wooProductId, $printifyProduct['images'] ?? []);
            
            // Process variants/attributes
            $this->variantHandler->processVariants($wooProductId, $printifyProduct);

            return true;
        } catch (\Exception $e) {
            error_log('Error updating product: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAllPrintifyProducts(): int
    {
        global $wpdb;
        
        // Get all products with Printify ID
        $productIds = $wpdb->get_col(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_id'"
        );
        
        if (empty($productIds)) {
            return 0;
        }
        
        $count = 0;
        foreach ($productIds as $productId) {
            // Force delete the product (skip trash)
            if (wp_delete_post($productId, true)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Update the synced products counter
     */
    private function updateSyncCounter(): void
    {
        $current_count = get_option('wpwps_products_synced', 0);
        update_option('wpwps_products_synced', $current_count + 1);
    }

    /**
     * Import all products from Printify
     * 
     * @param string $shopId
     * @return array Result of the operation
     */
    public function importAllProducts(string $shopId): array
    {
        // This is a placeholder - the actual implementation will use
        // the Action Scheduler to import all products in the background
        do_action('wpwps_start_all_products_import', $shopId);
        
        return [
            'status' => 'scheduled',
            'message' => 'All products import has been scheduled'
        ];
    }
}
