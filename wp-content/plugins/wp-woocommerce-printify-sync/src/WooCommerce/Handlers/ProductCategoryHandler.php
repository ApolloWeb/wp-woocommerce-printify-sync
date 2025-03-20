<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Handlers;

class ProductCategoryHandler
{
    /** @var array Category mapping from product_type to WooCommerce category IDs */
    private $categoryMapping;
    
    public function __construct()
    {
        // Load category mapping from options or define default
        $this->categoryMapping = get_option('wpwps_category_mapping', []);
        
        // If no mapping is defined, set up a default one
        if (empty($this->categoryMapping)) {
            $this->setupDefaultMapping();
        }
    }
    
    /**
     * Process and assign categories based on product_type
     *
     * @param int $productId WooCommerce product ID
     * @param array $printifyProduct Printify product data
     * @return void
     */
    public function processCategoriesFromProductType(int $productId, array $printifyProduct): void
    {
        $productType = $printifyProduct['product_type'] ?? '';
        
        if (empty($productType)) {
            return;
        }
        
        // Get category ID(s) for this product type
        $categoryIds = $this->getCategoryIdsForProductType($productType);
        
        if (!empty($categoryIds)) {
            wp_set_object_terms($productId, $categoryIds, 'product_cat');
        }
    }
    
    /**
     * Get WooCommerce category IDs for a given product type
     *
     * @param string $productType
     * @return array Array of category IDs
     */
    private function getCategoryIdsForProductType(string $productType): array
    {
        return $this->categoryMapping[$productType] ?? [];
    }
    
    /**
     * Set up default category mapping
     */
    private function setupDefaultMapping(): void
    {
        // Create some basic categories if they don't exist
        $defaultCategories = [
            'T-Shirts' => ['t-shirt', 'tee', 'apparel'],
            'Hoodies' => ['hoodie', 'sweatshirt'],
            'Accessories' => ['accessory', 'mug', 'phone case'],
            'Home Decor' => ['home', 'pillow', 'blanket', 'poster'],
            'Prints' => ['print', 'canvas', 'poster']
        ];
        
        $mapping = [];
        
        foreach ($defaultCategories as $categoryName => $types) {
            // Get or create the category
            $term = term_exists($categoryName, 'product_cat');
            if (!$term) {
                $term = wp_insert_term($categoryName, 'product_cat');
            }
            
            if (!is_wp_error($term)) {
                $categoryId = is_array($term) ? $term['term_id'] : $term;
                
                // Map each product type to this category
                foreach ($types as $type) {
                    $mapping[$type] = [$categoryId];
                }
            }
        }
        
        // Save the mapping
        update_option('wpwps_category_mapping', $mapping);
        $this->categoryMapping = $mapping;
    }
}
