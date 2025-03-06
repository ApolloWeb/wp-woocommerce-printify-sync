<?php
/**
 * Taxonomy Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class TaxonomyHelper {
    private static $instance = null;
    private $timestamp = '2025-03-05 18:39:40';
    private $user = 'ApolloWeb';
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Process product categories from Printify product
     */
    public function processCategories($printify_product) {
        $category_ids = [];
        $default_category_id = get_option('wpwprintifysync_default_category', 0);
        
        // Add default category
        if ($default_category_id > 0) {
            $category_ids[] = $default_category_id;
        }
        
        // Map blueprint to category
        if (!empty($printify_product['blueprint_id'])) {
            $category_id = $this->getBlueprintCategory($printify_product['blueprint_id']);
            if ($category_id) {
                $category_ids[] = $category_id;
            }
        }
        
        // Add from product type if available
        if (!empty($printify_product['product_type'])) {
            $type_category_id = $this->createOrGetCategory($printify_product['product_type']);
            if ($type_category_id) {
                $category_ids[] = $type_category_id;
            }
        }
        
        return array_unique($category_ids);
    }
    
    /**
     * Process product tags from Printify product
     */
    public function processTags($printify_product) {
        $tag_ids = [];
        
        // Process tags
        if (!empty($printify_product['tags'])) {
            $tags = is_array($printify_product['tags']) 
                ? $printify_product['tags'] 
                : explode(',', $printify_product['tags']);
                
            foreach ($tags as $tag_name) {
                $tag_name = trim($tag_name);
                if (empty($tag_name)) continue;
                
                $tag_id = $this->createOrGetTag($tag_name);
                if ($tag_id) {
                    $tag_ids[] = $tag_id;
                }
            }
        }
        
        // Add default tags
        $default_tags = get_option('wpwprintifysync_default_tags', []);
        if (!empty($default_tags) && is_array($default_tags)) {
            $tag_ids = array_merge($tag_ids, $default_tags);
        }
        
        return array_unique($tag_ids);
    }
    
    /**
     * Create or get category
     */
    public function createOrGetCategory($name) {
        $slug = sanitize_title($name);
        $category = get_term_by('slug', $slug, 'product_cat');
        
        if ($category) {
            return $category->term_id;
        }
        
        $result = wp_insert_term($name, 'product_cat', ['slug' => $slug]);
        return is_wp_error($result) ? false : $result['term_id'];
    }
    
    /**
     * Create or get tag
     */
    public function createOrGetTag($name) {
        $slug = sanitize_title($name);
        $tag = get_term_by('slug', $slug, 'product_tag');
        
        if ($tag) {
            return $tag->term_id;
        }
        
        $result = wp_insert_term($name, 'product_tag', ['slug' => $slug]);
        return is_wp_error($result) ? false : $result['term_id'];
    }
    
    /**
     * Map blueprint ID to category
     */
    public function getBlueprintCategory($blueprint_id) {
        $blueprint_categories = [
            '71' => 'tshirts',       // T-shirt
            '82' => 'hoodies',       // Hoodie
            '94' => 'accessories',   // Mug
            '105' => 'accessories',  // Phone Case
            '113' => 'totes',        // Tote Bag
        ];
        
        if (!isset($blueprint_categories[$blueprint_id])) {
            return false;
        }
        
        $slug = $blueprint_categories[$blueprint_id];
        $category = get_term_by('slug', $slug, 'product_cat');
        
        if ($category) {
            return $category->term_id;
        }
        
        // Create if doesn't exist
        $name = ucfirst(str_replace('-', ' ', $slug));
        $result = wp_insert_term($name, 'product_cat', ['slug' => $slug]);
        
        return is_wp_error($result) ? false : $result['term_id'];
    }
}