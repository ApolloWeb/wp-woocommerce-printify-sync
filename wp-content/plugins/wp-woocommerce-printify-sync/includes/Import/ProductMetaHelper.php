<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Import;

/**
 * Helper class to work with Printify product meta data
 */
class ProductMetaHelper
{
    /**
     * Meta key for Printify product ID
     */
    const META_PRINTIFY_PRODUCT_ID = '_printify_product_id';
    
    /**
     * Meta key for Printify provider ID
     */
    const META_PRINTIFY_PROVIDER_ID = '_printify_provider_id';
    
    /**
     * Meta key for Printify variant IDs
     */
    const META_PRINTIFY_VARIANT_IDS = '_printify_variant_ids';
    
    /**
     * Meta key for last synced timestamp
     */
    const META_PRINTIFY_LAST_SYNCED = '_printify_last_synced';
    
    /**
     * Meta key for Printify variant ID (used on variations)
     */
    const META_PRINTIFY_VARIANT_ID = '_printify_variant_id';
    
    /**
     * Meta key for Printify cost price
     */
    const META_PRINTIFY_COST_PRICE = '_printify_cost_price';
    
    /**
     * Meta key for Printify source URL (used for images)
     */
    const META_PRINTIFY_SOURCE_URL = '_printify_source_url';
    
    /**
     * Get Printify product ID for a WooCommerce product
     *
     * @param int|\WC_Product $product WooCommerce product ID or object
     * @return string|false Printify product ID or false if not found
     */
    public static function getPrintifyProductId($product)
    {
        $productId = is_a($product, 'WC_Product') ? $product->get_id() : $product;
        return get_post_meta($productId, self::META_PRINTIFY_PRODUCT_ID, true);
    }
    
    /**
     * Get Printify provider ID for a WooCommerce product
     *
     * @param int|\WC_Product $product WooCommerce product ID or object
     * @return string|false Printify provider ID or false if not found
     */
    public static function getPrintifyProviderId($product)
    {
        $productId = is_a($product, 'WC_Product') ? $product->get_id() : $product;
        return get_post_meta($productId, self::META_PRINTIFY_PROVIDER_ID, true);
    }
    
    /**
     * Get Printify variant IDs for a WooCommerce product
     *
     * @param int|\WC_Product $product WooCommerce product ID or object
     * @return array Printify variant IDs
     */
    public static function getPrintifyVariantIds($product)
    {
        $productId = is_a($product, 'WC_Product') ? $product->get_id() : $product;
        $variantIds = get_post_meta($productId, self::META_PRINTIFY_VARIANT_IDS, true);
        return is_array($variantIds) ? $variantIds : [];
    }
    
    /**
     * Get last sync timestamp for a WooCommerce product
     *
     * @param int|\WC_Product $product WooCommerce product ID or object
     * @return string|false Timestamp or false if not found
     */
    public static function getLastSyncedTimestamp($product)
    {
        $productId = is_a($product, 'WC_Product') ? $product->get_id() : $product;
        return get_post_meta($productId, self::META_PRINTIFY_LAST_SYNCED, true);
    }
    
    /**
     * Find WooCommerce product ID by Printify product ID
     *
     * @param string $printifyProductId Printify product ID
     * @return int|false WooCommerce product ID or false if not found
     */
    public static function findProductByPrintifyId($printifyProductId)
    {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = %s 
            AND meta_value = %s 
            LIMIT 1",
            self::META_PRINTIFY_PRODUCT_ID,
            $printifyProductId
        );
        
        $result = $wpdb->get_var($query);
        
        return $result ? (int) $result : false;
    }
    
    /**
     * Find WooCommerce variation ID by Printify variant ID
     *
     * @param string $printifyVariantId Printify variant ID
     * @param int $parentProductId Optional parent product ID to limit search
     * @return int|false WooCommerce variation ID or false if not found
     */
    public static function findVariationByPrintifyVariantId($printifyVariantId, $parentProductId = null)
    {
        global $wpdb;
        
        if ($parentProductId) {
            $query = $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = %s
                AND pm.meta_value = %s
                AND p.post_parent = %d
                LIMIT 1",
                self::META_PRINTIFY_VARIANT_ID,
                $printifyVariantId,
                $parentProductId
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = %s
                AND meta_value = %s
                LIMIT 1",
                self::META_PRINTIFY_VARIANT_ID,
                $printifyVariantId
            );
        }
        
        $result = $wpdb->get_var($query);
        
        return $result ? (int) $result : false;
    }
    
    /**
     * Check if a WooCommerce product is linked to Printify
     *
     * @param int|\WC_Product $product WooCommerce product ID or object
     * @return bool
     */
    public static function isLinkedToPrintify($product)
    {
        return (bool) self::getPrintifyProductId($product);
    }
    
    /**
     * Update Printify meta data for a product
     *
     * @param int|\WC_Product $product WooCommerce product ID or object
     * @param array $printifyData Printify product data
     * @return void
     */
    public static function updatePrintifyMeta($product, $printifyData)
    {
        if (!is_a($product, 'WC_Product')) {
            $product = wc_get_product($product);
        }
        
        if (!$product) {
            return;
        }
        
        // Set product meta to track Printify relationship
        $product->update_meta_data(self::META_PRINTIFY_PRODUCT_ID, $printifyData['id']);
        $product->update_meta_data(self::META_PRINTIFY_PROVIDER_ID, $printifyData['print_provider']['id'] ?? '');
        $product->update_meta_data(self::META_PRINTIFY_LAST_SYNCED, current_time('mysql'));
        
        $product->save();
    }
    
    /**
     * Get all WooCommerce products linked to Printify
     *
     * @param int $limit Maximum number of products to return (0 for all)
     * @param int $offset Offset for pagination
     * @return array Array of WC_Product objects
     */
    public static function getLinkedProducts($limit = 0, $offset = 0)
    {
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $limit ?: -1,
            'offset' => $offset,
            'meta_query' => [
                [
                    'key' => self::META_PRINTIFY_PRODUCT_ID,
                    'compare' => 'EXISTS',
                ]
            ],
            'fields' => 'ids',
        ];
        
        $query = new \WP_Query($args);
        $productIds = $query->posts;
        
        return array_map('wc_get_product', $productIds);
    }
}
