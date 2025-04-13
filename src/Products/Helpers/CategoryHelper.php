<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Products\Helpers;

use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;

/**
 * Helper class for managing product categories
 */
class CategoryHelper {
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var array Category mapping cache
     */
    private $category_cache = [];
    
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Set product categories based on Printify data
     *
     * @param \WC_Product $product WooCommerce product
     * @param string $product_type Printify product type
     * @param array $extra_categories Additional category data
     * @return array Array of assigned category IDs
     */
    public function set_product_categories($product, $product_type, $extra_categories = []) {
        $category_ids = [];
        
        // Get or create main category from product type
        if (!empty($product_type)) {
            $main_category_id = $this->get_or_create_category($product_type);
            if ($main_category_id) {
                $category_ids[] = $main_category_id;
            }
        }
        
        // Process any extra categories if provided
        if (!empty($extra_categories) && is_array($extra_categories)) {
            foreach ($extra_categories as $category) {
                if (isset($category['name'])) {
                    // Check if this is a subcategory with a parent
                    if (isset($category['parent'])) {
                        $parent_id = $this->get_or_create_category($category['parent']);
                        $cat_id = $this->get_or_create_category($category['name'], $parent_id);
                    } else {
                        $cat_id = $this->get_or_create_category($category['name']);
                    }
                    
                    if ($cat_id && !in_array($cat_id, $category_ids)) {
                        $category_ids[] = $cat_id;
                    }
                }
            }
        }
        
        // Set the categories on the product
        if (!empty($category_ids)) {
            $product->set_category_ids($category_ids);
        }
        
        return $category_ids;
    }
    
    /**
     * Get or create category by name
     *
     * @param string $category_name Category name
     * @param int $parent_id Parent category ID
     * @return int|false Category ID or false on failure
     */
    public function get_or_create_category($category_name, $parent_id = 0) {
        // Check cache first
        $cache_key = $category_name . '_' . $parent_id;
        if (isset($this->category_cache[$cache_key])) {
            return $this->category_cache[$cache_key];
        }
        
        // Try to find existing category
        $term = get_term_by('name', $category_name, 'product_cat');
        
        if ($term) {
            $this->category_cache[$cache_key] = $term->term_id;
            return $term->term_id;
        }
        
        // Create new category
        $slug = sanitize_title($category_name);
        $result = wp_insert_term($category_name, 'product_cat', [
            'slug' => $slug,
            'parent' => $parent_id
        ]);
        
        if (is_wp_error($result)) {
            $this->logger->log_error(
                'categories', 
                sprintf('Failed to create category %s: %s', $category_name, $result->get_error_message())
            );
            return false;
        }
        
        $this->logger->log_info(
            'categories', 
            sprintf('Created category %s with ID %d', $category_name, $result['term_id'])
        );
        
        $this->category_cache[$cache_key] = $result['term_id'];
        return $result['term_id'];
    }
    
    /**
     * Map Printify product types to WooCommerce categories
     *
     * @param string $printify_type Printify product type
     * @return array Category info (ID, Name, Slug)
     */
    public function map_printify_type_to_category($printify_type) {
        // Get the custom mapping if available
        $mapping = get_option('wpwps_category_mapping', []);
        
        if (isset($mapping[$printify_type])) {
            return $mapping[$printify_type];
        }
        
        // Default mapping is a direct 1:1 mapping of the type name
        $cat_id = $this->get_or_create_category($printify_type);
        
        return [
            'id' => $cat_id,
            'name' => $printify_type,
            'slug' => sanitize_title($printify_type)
        ];
    }
    
    /**
     * Parse hierarchical categories from Printify data
     *
     * @param mixed $printify_category_data Category data from Printify
     * @return array Category hierarchy
     */
    public function parse_printify_categories($printify_category_data) {
        $categories = [];
        
        // Default implementation, assuming printify_category_data is a string
        if (is_string($printify_category_data)) {
            // Split by a delimiter if present (like "Parent > Child")
            if (strpos($printify_category_data, '>') !== false) {
                $parts = array_map('trim', explode('>', $printify_category_data));
                
                // First part is the parent
                $parent = array_shift($parts);
                $parent_id = $this->get_or_create_category($parent);
                $categories[] = ['name' => $parent, 'id' => $parent_id];
                
                // Subsequent parts are children
                foreach ($parts as $child) {
                    $child_id = $this->get_or_create_category($child, $parent_id);
                    $categories[] = [
                        'name' => $child, 
                        'id' => $child_id,
                        'parent' => $parent,
                        'parent_id' => $parent_id
                    ];
                    
                    // Update parent for next child in hierarchy
                    $parent = $child;
                    $parent_id = $child_id;
                }
            } else {
                // Just a single category
                $cat_id = $this->get_or_create_category($printify_category_data);
                $categories[] = [
                    'name' => $printify_category_data,
                    'id' => $cat_id
                ];
            }
        } 
        // If it's an array, process each item
        elseif (is_array($printify_category_data)) {
            foreach ($printify_category_data as $category) {
                if (is_string($category)) {
                    $result = $this->parse_printify_categories($category);
                    $categories = array_merge($categories, $result);
                }
            }
        }
        
        return $categories;
    }
}
