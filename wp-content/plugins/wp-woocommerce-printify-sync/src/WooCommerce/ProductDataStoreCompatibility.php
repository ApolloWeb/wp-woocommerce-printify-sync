<?php

namespace ApolloWeb\WPWooCommercePrintifySync\WooCommerce;

/**
 * Provides compatibility methods for working with WooCommerce products
 */
class ProductDataStoreCompatibility
{
    /**
     * Check if HPOS is active
     *
     * @return bool
     */
    public static function isHposActive(): bool
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        
        return false;
    }
    
    /**
     * Get a product ID by its Printify ID
     *
     * @param string $printifyId
     * @return int|null
     */
    public static function getProductIdByPrintifyId(string $printifyId): ?int
    {
        global $wpdb;
        
        // Get the product ID from postmeta
        $productId = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_id' AND meta_value = %s",
            $printifyId
        ));
        
        // If no product found with this ID, return null
        if (!$productId) {
            return null;
        }
        
        // Check if the product post actually exists and is not in trash
        $post_status = get_post_status($productId);
        if (!$post_status || $post_status === 'trash') {
            // Clean up orphaned meta
            delete_post_meta($productId, '_printify_id');
            return null;
        }
        
        return (int) $productId;
    }
}
