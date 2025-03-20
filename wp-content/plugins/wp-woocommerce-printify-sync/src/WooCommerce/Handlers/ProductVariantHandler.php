<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Handlers;

class ProductVariantHandler
{
    /**
     * Process and create product variants and attributes
     *
     * @param int $productId WooCommerce product ID
     * @param array $printifyProduct Printify product data
     * @return void
     */
    public function processVariants(int $productId, array $printifyProduct): void
    {
        if (empty($printifyProduct['variants'])) {
            return;
        }
        
        // Extract all possible attributes from variants
        $attributes = $this->extractAttributes($printifyProduct['variants']);
        
        // Set product attributes
        $this->setProductAttributes($productId, $attributes);
        
        // Create variations
        $this->createVariations($productId, $printifyProduct['variants'], $attributes);
    }
    
    /**
     * Extract unique attributes from variants
     *
     * @param array $variants
     * @return array
     */
    private function extractAttributes(array $variants): array
    {
        $attributes = [];
        
        foreach ($variants as $variant) {
            if (empty($variant['options'])) {
                continue;
            }
            
            foreach ($variant['options'] as $option => $value) {
                if (!isset($attributes[$option])) {
                    $attributes[$option] = [];
                }
                
                if (!in_array($value, $attributes[$option])) {
                    $attributes[$option][] = $value;
                }
            }
        }
        
        return $attributes;
    }
    
    /**
     * Set product attributes
     *
     * @param int $productId
     * @param array $attributes
     * @return void
     */
    private function setProductAttributes(int $productId, array $attributes): void
    {
        $productAttributes = [];
        
        foreach ($attributes as $name => $values) {
            // Create attribute
            $attribute = [
                'name' => wc_clean($name),
                'value' => implode('|', $values),
                'position' => count($productAttributes),
                'is_visible' => 1,
                'is_variation' => 1,
                'is_taxonomy' => 0
            ];
            
            $productAttributes['pa_' . sanitize_title($name)] = $attribute;
        }
        
        update_post_meta($productId, '_product_attributes', $productAttributes);
    }
    
    /**
     * Create product variations
     *
     * @param int $productId
     * @param array $variants
     * @param array $attributes
     * @return void
     */
    private function createVariations(int $productId, array $variants, array $attributes): void
    {
        // First, remove existing variations to prevent duplicates
        $this->deleteExistingVariations($productId);
        
        foreach ($variants as $variant) {
            // Create variation
            $variation = [
                'post_title' => 'Variation #' . $variant['id'] . ' of ' . get_the_title($productId),
                'post_name' => 'product-' . $productId . '-variation-' . $variant['id'],
                'post_status' => 'publish',
                'post_parent' => $productId,
                'post_type' => 'product_variation',
                'guid' => get_permalink($productId) . '?variant=' . $variant['id']
            ];
            
            $variationId = wp_insert_post($variation);
            
            if (!is_wp_error($variationId)) {
                // Set variation attributes
                foreach ($variant['options'] as $option => $value) {
                    $attributeName = 'attribute_pa_' . sanitize_title($option);
                    update_post_meta($variationId, $attributeName, sanitize_title($value));
                }
                
                // Set variation metadata
                update_post_meta($variationId, '_sku', $variant['sku'] ?? '');
                update_post_meta($variationId, '_regular_price', $variant['price'] ?? '');
                update_post_meta($variationId, '_price', $variant['price'] ?? '');
                update_post_meta($variationId, '_manage_stock', 'yes');
                update_post_meta($variationId, '_stock', $variant['stock'] ?? 0);
                update_post_meta($variationId, '_stock_status', ($variant['stock'] > 0) ? 'instock' : 'outofstock');
                update_post_meta($variationId, '_printify_variant_id', $variant['id']);
            }
        }
        
        // Make sure WooCommerce knows this is a variable product
        WC_Product_Variable::sync($productId);
    }
    
    /**
     * Delete existing variations for a product
     *
     * @param int $productId
     * @return void
     */
    private function deleteExistingVariations(int $productId): void
    {
        $variations = get_posts([
            'post_parent' => $productId,
            'post_type' => 'product_variation',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        if ($variations) {
            foreach ($variations as $variationId) {
                wp_delete_post($variationId, true);
            }
        }
    }
}
