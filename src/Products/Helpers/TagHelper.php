<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Products\Helpers;

use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;

/**
 * Helper class for managing product tags
 */
class TagHelper {
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var array Tag mapping cache
     */
    private $tag_cache = [];
    
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Set product tags based on Printify data
     *
     * @param \WC_Product $product WooCommerce product
     * @param array $printify_tags Array of tag names
     * @return array Array of assigned tag IDs
     */
    public function set_product_tags($product, $printify_tags) {
        if (empty($printify_tags)) {
            return [];
        }
        
        $tag_ids = [];
        
        // Handle both string and array formats
        if (is_string($printify_tags)) {
            $printify_tags = explode(',', $printify_tags);
        }
        
        // Create or get each tag
        if (is_array($printify_tags)) {
            foreach ($printify_tags as $tag) {
                $tag = trim($tag);
                if (empty($tag)) {
                    continue;
                }
                
                $tag_id = $this->get_or_create_tag($tag);
                if ($tag_id && !in_array($tag_id, $tag_ids)) {
                    $tag_ids[] = $tag_id;
                }
            }
        }
        
        // Set the tags on the product
        if (!empty($tag_ids)) {
            $product->set_tag_ids($tag_ids);
        }
        
        return $tag_ids;
    }
    
    /**
     * Get or create tag by name
     *
     * @param string $tag_name Tag name
     * @return int|false Tag ID or false on failure
     */
    public function get_or_create_tag($tag_name) {
        // Check cache first
        if (isset($this->tag_cache[$tag_name])) {
            return $this->tag_cache[$tag_name];
        }
        
        // Try to find existing tag
        $term = get_term_by('name', $tag_name, 'product_tag');
        
        if ($term) {
            $this->tag_cache[$tag_name] = $term->term_id;
            return $term->term_id;
        }
        
        // Create new tag
        $slug = sanitize_title($tag_name);
        $result = wp_insert_term($tag_name, 'product_tag', [
            'slug' => $slug
        ]);
        
        if (is_wp_error($result)) {
            $this->logger->log_error(
                'tags', 
                sprintf('Failed to create tag %s: %s', $tag_name, $result->get_error_message())
            );
            return false;
        }
        
        $this->logger->log_info(
            'tags', 
            sprintf('Created tag %s with ID %d', $tag_name, $result['term_id'])
        );
        
        $this->tag_cache[$tag_name] = $result['term_id'];
        return $result['term_id'];
    }
    
    /**
     * Process and normalize tags from Printify
     *
     * @param mixed $printify_tags Tag data from Printify
     * @return array Normalized array of tag names
     */
    public function normalize_printify_tags($printify_tags) {
        $tags = [];
        
        if (is_string($printify_tags)) {
            // Convert comma-separated string to array
            $tags = array_map('trim', explode(',', $printify_tags));
        } 
        elseif (is_array($printify_tags)) {
            foreach ($printify_tags as $tag) {
                if (is_string($tag)) {
                    $tags[] = trim($tag);
                } elseif (is_array($tag) && isset($tag['name'])) {
                    $tags[] = trim($tag['name']);
                }
            }
        }
        
        // Remove empty tags and duplicates
        return array_unique(array_filter($tags));
    }
    
    /**
     * Get all tags for a product
     *
     * @param int $product_id WooCommerce product ID
     * @return array Array of tag info (ID, Name, Slug)
     */
    public function get_product_tags($product_id) {
        $tags = [];
        $terms = get_the_terms($product_id, 'product_tag');
        
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $tags[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug
                ];
            }
        }
        
        return $tags;
    }
}
