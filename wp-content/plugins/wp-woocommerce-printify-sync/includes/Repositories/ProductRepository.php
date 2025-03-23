<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

/**
 * Product Repository
 */
class ProductRepository {
    // ...existing code...
    
    /**
     * Get all products linked to Printify
     *
     * @param int $limit Optional. Maximum products to retrieve (0 for all)
     * @param int $offset Optional. Offset for pagination
     * @return array Array of product data
     */
    public function getAllPrintifyProducts(int $limit = 0, int $offset = 0): array {
        global $wpdb;
        
        $query = "
            SELECT p.ID as product_id, pm.meta_value as printify_id
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ('product', 'product_variation')
            AND p.post_status = 'publish'
            AND pm.meta_key = '_printify_product_id'
            AND pm.meta_value != ''
        ";
        
        if ($limit > 0) {
            $query .= $wpdb->prepare(" LIMIT %d, %d", $offset, $limit);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        return $results ?: [];
    }
    
    /**
     * Count total Printify products
     *
     * @return int Count of Printify products
     */
    public function countPrintifyProducts(): int {
        global $wpdb;
        
        return (int) $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ('product', 'product_variation')
            AND p.post_status = 'publish'
            AND pm.meta_key = '_printify_product_id'
            AND pm.meta_value != ''
        ");
    }
    
    /**
     * Get product by Printify ID
     *
     * @param string $printify_id Printify product ID
     * @return int|null WooCommerce product ID or null if not found
     */
    public function getProductByPrintifyId(string $printify_id): ?int {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_printify_product_id' AND meta_value = %s 
             LIMIT 1",
            $printify_id
        ));
        
        return $product_id ? (int) $product_id : null;
    }
    
    // ...existing code...
}
