<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Products\Helpers;

use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;

/**
 * Helper class for managing product variants
 */
class VariantHelper {
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var array Attribute mapping cache
     */
    private $attribute_cache = [];
    
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Process variants from Printify product
     *
     * @param int $product_id WooCommerce product ID
     * @param array $variants Array of Printify variants
     * @return bool Success status
     */
    public function process_variants($product_id, $variants) {
        if (empty($variants)) {
            $this->logger->log_info('variants', 'No variants to process');
            return false;
        }
        
        try {
            // Extract attributes
            $attributes = $this->extract_attributes_from_variants($variants);
            
            // Set product attributes
            $this->set_product_attributes($product_id, $attributes);
            
            // Delete existing variations to avoid duplicates
            $this->delete_existing_variations($product_id);
            
            // Create variations
            foreach ($variants as $variant) {
                $this->create_variation($product_id, $variant, $attributes);
            }
            
            // Set default attributes
            $this->set_default_attributes($product_id, $variants[0], $attributes);
            
            // Update parent product's price from variations
            $this->update_parent_product_price($product_id);
            
            $this->logger->log_success(
                'variants', 
                sprintf('Successfully processed %d variants for product %d', count($variants), $product_id)
            );
            
            return true;
        } catch (\Exception $e) {
            $this->logger->log_error(
                'variants', 
                sprintf('Error processing variants: %s', $e->getMessage())
            );
            return false;
        }
    }
    
    /**
     * Extract attributes from variants
     *
     * @param array $variants Printify variants
     * @return array Attributes array
     */
    public function extract_attributes_from_variants($variants) {
        $attributes = [];
        
        foreach ($variants as $variant) {
            if (!empty($variant['options'])) {
                foreach ($variant['options'] as $option_name => $option_value) {
                    $attribute_name = wc_clean($option_name);
                    $attribute_value = wc_clean($option_value);
                    
                    if (!isset($attributes[$attribute_name])) {
                        $attributes[$attribute_name] = [];
                    }
                    
                    if (!in_array($attribute_value, $attributes[$attribute_name])) {
                        $attributes[$attribute_name][] = $attribute_value;
                    }
                }
            }
        }
        
        return $attributes;
    }
    
    /**
     * Set product attributes
     *
     * @param int $product_id WooCommerce product ID
     * @param array $attributes Attributes array
     * @return array Product attributes data
     */
    public function set_product_attributes($product_id, $attributes) {
        $product_attributes = [];
        
        foreach ($attributes as $name => $values) {
            $attribute_id = $this->get_attribute_taxonomy_id($name);
            
            // Create attribute taxonomy if it doesn't exist
            if (!$attribute_id) {
                $attribute_id = $this->create_attribute_taxonomy($name);
            }
            
            if ($attribute_id) {
                $attribute_name = wc_attribute_taxonomy_name_by_id($attribute_id);
            } else {
                $attribute_name = sanitize_title($name);
            }
            
            // Create attribute terms
            $term_ids = [];
            foreach ($values as $value) {
                if ($attribute_id) {
                    // Global attribute
                    $term = term_exists($value, $attribute_name);
                    
                    if (!$term) {
                        $term = wp_insert_term($value, $attribute_name);
                    }
                    
                    if (!is_wp_error($term)) {
                        $term_ids[] = $term['term_id'];
                    }
                }
            }
            
            // Store attribute data
            $product_attributes[$attribute_name] = [
                'name' => $attribute_name,
                'value' => '',
                'position' => 0,
                'is_visible' => 1,
                'is_variation' => 1,
                'is_taxonomy' => 1,
                'terms' => $term_ids
            ];
            
            // Set attribute terms if global attribute
            if ($attribute_id) {
                wp_set_object_terms($product_id, $term_ids, $attribute_name);
            }
        }
        
        // Save attributes to product
        update_post_meta($product_id, '_product_attributes', $product_attributes);
        
        return $product_attributes;
    }
    
    /**
     * Create a product variation
     *
     * @param int $product_id WooCommerce product ID
     * @param array $variant Printify variant
     * @param array $attributes Product attributes
     * @return int|false Variation ID or false on failure
     */
    public function create_variation($product_id, $variant, $attributes) {
        try {
            // Create the variation
            $variation = new \WC_Product_Variation();
            $variation->set_parent_id($product_id);
            
            // Set variation attributes
            if (!empty($variant['options'])) {
                $variation_attributes = [];
                
                foreach ($variant['options'] as $name => $value) {
                    $attribute_name = 'attribute_' . sanitize_title(wc_clean($name));
                    $variation_attributes[$attribute_name] = wc_clean($value);
                }
                
                $variation->set_attributes($variation_attributes);
            }
            
            // Set variation data
            $variation->set_status('publish');
            
            if (!empty($variant['sku'])) {
                $variation->set_sku($variant['sku']);
            }
            
            if (!empty($variant['price'])) {
                $variation->set_regular_price($variant['price']);
            }
            
            if (!empty($variant['cost'])) {
                $variation->update_meta_data('_printify_cost_price', $variant['cost']);
            }
            
            // Set Printify metadata
            $variation->update_meta_data('_printify_variant_id', $variant['id']);
            $variation->update_meta_data('_printify_is_synced', true);
            
            // Set stock status
            $in_stock = isset($variant['is_enabled']) ? (bool)$variant['is_enabled'] : true;
            $variation->set_stock_status($in_stock ? 'instock' : 'outofstock');
            
            // Save the variation
            $variation_id = $variation->save();
            
            $this->logger->log_info(
                'variants', 
                sprintf('Created variation %d for product %d', $variation_id, $product_id)
            );
            
            return $variation_id;
        } catch (\Exception $e) {
            $this->logger->log_error(
                'variants', 
                sprintf('Error creating variation: %s', $e->getMessage())
            );
            return false;
        }
    }
    
    /**
     * Delete existing product variations
     *
     * @param int $product_id WooCommerce product ID
     * @return int Number of variations deleted
     */
    public function delete_existing_variations($product_id) {
        $product = wc_get_product($product_id);
        $deleted = 0;
        
        if (!$product || !($product instanceof \WC_Product_Variable)) {
            return 0;
        }
        
        $variation_ids = $product->get_children();
        
        foreach ($variation_ids as $variation_id) {
            wp_delete_post($variation_id, true);
            $deleted++;
        }
        
        $this->logger->log_info(
            'variants', 
            sprintf('Deleted %d existing variations for product %d', $deleted, $product_id)
        );
        
        return $deleted;
    }
    
    /**
     * Set default attributes for the product
     *
     * @param int $product_id WooCommerce product ID
     * @param array $default_variant Default Printify variant
     * @param array $attributes Product attributes
     * @return bool Success status
     */
    public function set_default_attributes($product_id, $default_variant, $attributes) {
        if (empty($default_variant) || empty($attributes)) {
            return false;
        }
        
        // Get options from default variant
        $options = !empty($default_variant['options']) ? $default_variant['options'] : [];
        
        if (empty($options)) {
            return false;
        }
        
        $default_attributes = [];
        
        foreach ($options as $name => $value) {
            $attribute_name = sanitize_title(wc_clean($name));
            $default_attributes[$attribute_name] = wc_clean($value);
        }
        
        if (!empty($default_attributes)) {
            update_post_meta($product_id, '_default_attributes', $default_attributes);
            
            $this->logger->log_info(
                'variants', 
                sprintf('Set default attributes for product %d', $product_id)
            );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Update parent product's price from variations
     *
     * @param int $product_id WooCommerce product ID
     * @return bool Success status
     */
    public function update_parent_product_price($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product || !($product instanceof \WC_Product_Variable)) {
            return false;
        }
        
        // This updates the min/max/regular prices
        $product->sync_price();
        
        // Save changes
        $product->save();
        
        return true;
    }
    
    /**
     * Get attribute taxonomy ID by name
     *
     * @param string $name Attribute name
     * @return int|null Attribute ID or null if not found
     */
    public function get_attribute_taxonomy_id($name) {
        if (isset($this->attribute_cache[$name])) {
            return $this->attribute_cache[$name];
        }
        
        $attribute_id = wc_attribute_taxonomy_id_by_name($name);
        
        if ($attribute_id) {
            $this->attribute_cache[$name] = $attribute_id;
        }
        
        return $attribute_id;
    }
    
    /**
     * Create attribute taxonomy
     *
     * @param string $name Attribute name
     * @return int|false Attribute ID or false on failure
     */
    public function create_attribute_taxonomy($name) {
        $clean_name = wc_sanitize_taxonomy_name($name);
        
        // Check if taxonomy exists
        $taxonomy = wc_attribute_taxonomy_name($clean_name);
        if (taxonomy_exists($taxonomy)) {
            $attribute_id = wc_attribute_taxonomy_id_by_name($clean_name);
            $this->attribute_cache[$name] = $attribute_id;
            return $attribute_id;
        }
        
        // Create attribute
        $args = [
            'name' => $name,
            'slug' => $clean_name,
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ];
        
        $result = wc_create_attribute($args);
        
        if (is_wp_error($result)) {
            $this->logger->log_error(
                'variants', 
                sprintf('Failed to create attribute %s: %s', $name, $result->get_error_message())
            );
            return false;
        }
        
        // Register the taxonomy
        register_taxonomy(
            wc_attribute_taxonomy_name($clean_name),
            'product',
            [
                'hierarchical' => false,
                'show_ui' => true,
                'query_var' => true,
            ]
        );
        
        // Clear caches
        delete_transient('wc_attribute_taxonomies');
        
        $this->logger->log_info(
            'variants', 
            sprintf('Created attribute taxonomy %s with ID %d', $name, $result)
        );
        
        $this->attribute_cache[$name] = $result;
        return $result;
    }
    
    /**
     * Get all variations for a product
     * 
     * @param int $product_id WooCommerce product ID
     * @return array Array of variation data
     */
    public function get_product_variations($product_id) {
        $product = wc_get_product($product_id);
        $variations = [];
        
        if (!$product || !($product instanceof \WC_Product_Variable)) {
            return $variations;
        }
        
        $variation_ids = $product->get_children();
        
        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);
            
            if ($variation) {
                $variations[] = [
                    'id' => $variation_id,
                    'sku' => $variation->get_sku(),
                    'price' => $variation->get_price(),
                    'regular_price' => $variation->get_regular_price(),
                    'sale_price' => $variation->get_sale_price(),
                    'stock_status' => $variation->get_stock_status(),
                    'attributes' => $variation->get_attributes(),
                    'printify_variant_id' => $variation->get_meta('_printify_variant_id'),
                    'printify_cost_price' => $variation->get_meta('_printify_cost_price')
                ];
            }
        }
        
        return $variations;
    }
}
