<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Handlers;

class ProductTagsHandler
{
    /**
     * Process and assign tags to a product
     *
     * @param int $productId WooCommerce product ID
     * @param array $printifyProduct Printify product data
     * @return void
     */
    public function processProductTags(int $productId, array $printifyProduct): void
    {
        // Extract tags from Printify product
        $tags = $this->extractTagsFromPrintifyProduct($printifyProduct);
        
        if (!empty($tags)) {
            wp_set_object_terms($productId, $tags, 'product_tag');
        }
    }
    
    /**
     * Extract tags from Printify product data
     *
     * @param array $printifyProduct
     * @return array Array of tag names
     */
    private function extractTagsFromPrintifyProduct(array $printifyProduct): array
    {
        $tags = [];
        
        // Extract from explicit tags field if available
        if (!empty($printifyProduct['tags']) && is_array($printifyProduct['tags'])) {
            $tags = array_merge($tags, $printifyProduct['tags']);
        }
        
        // You can add additional tag extraction logic here
        // For example, extracting from other fields or adding default tags
        
        return array_unique(array_filter($tags));
    }
}
