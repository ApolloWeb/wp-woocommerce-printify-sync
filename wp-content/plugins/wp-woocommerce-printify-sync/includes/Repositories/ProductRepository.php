<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

/**
 * Product Repository
 * 
 * Handles data storage and retrieval for products
 */
class ProductRepository {
    /**
     * Get WooCommerce product ID by Printify product ID
     *
     * @param string $printify_id Printify product ID
     * @return int|null WooCommerce product ID or null if not found
     */
    public function getWooProductByPrintifyId(string $printify_id): ?int {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_printify_product_id' AND meta_value = %s LIMIT 1",
            $printify_id
        ));
        
        return $product_id ? (int) $product_id : null;
    }
    
    /**
     * Get WooCommerce variation ID by Printify variant ID
     *
     * @param int $product_id WooCommerce product ID
     * @param string $variant_id Printify variant ID
     * @return int|null WooCommerce variation ID or null if not found
     */
    public function getWooVariationByPrintifyId(int $product_id, string $variant_id): ?int {
        global $wpdb;
        
        // First try direct match
        $variation_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_printify_variant_id' AND meta_value = %s LIMIT 1",
            $variant_id
        ));
        
        if ($variation_id) {
            return (int) $variation_id;
        }
        
        // No direct match, try looking through the parent product's variations
        $variations = get_posts([
            'post_type' => 'product_variation',
            'post_status' => 'publish',
            'post_parent' => $product_id,
            'posts_per_page' => -1
        ]);
        
        foreach ($variations as $variation) {
            $var_id = get_post_meta($variation->ID, '_printify_variant_id', true);
            
            if ($var_id === $variant_id) {
                return (int) $variation->ID;
            }
        }
        
        return null;
    }
    
    /**
     * Create a placeholder product for an unknown Printify product
     *
     * @param array $item Printify line item
     * @return int WooCommerce product ID
     */
    public function createPlaceholderProduct(array $item): int {
        // Create a simple product
        $product = new \WC_Product_Simple();
        
        $product->set_name($item['title'] ?? sprintf(__('Printify Product %s', 'wp-woocommerce-printify-sync'), $item['product_id']));
        $product->set_status('private'); // Not publicly visible
        $product->set_catalog_visibility('hidden');
        $product->set_regular_price($item['price'] / 100);
        
        // Save the product to get an ID
        $product_id = $product->save();
        
        // Set Printify metadata
        update_post_meta($product_id, '_printify_product_id', $item['product_id']);
        update_post_meta($product_id, '_printify_is_placeholder', '1');
        
        return $product_id;
    }
}
