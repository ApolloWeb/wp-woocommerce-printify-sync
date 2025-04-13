<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Products;

/**
 * Product Sync Service Interface
 */
interface ProductSyncServiceInterface {
    /**
     * Start a full product sync
     *
     * @param bool $force Force sync even if products exist
     * @return void
     */
    public function start_full_sync($force = false);
    
    /**
     * Schedule product import as an async action
     *
     * @param string $product_id Printify Product ID
     * @return void
     */
    public function schedule_product_import($product_id);
    
    /**
     * Import a single product from Printify
     *
     * @param string $printify_product_id Printify Product ID
     * @return int|WP_Error WooCommerce product ID or error
     */
    public function import_product($printify_product_id);
    
    /**
     * Import product image
     *
     * @param int    $product_id  WooCommerce product ID
     * @param string $image_url   Image URL
     * @param bool   $is_featured Whether this is a featured image
     * @return int|WP_Error Attachment ID or error
     */
    public function import_product_image($product_id, $image_url, $is_featured = false);
    
    /**
     * Import variant image
     *
     * @param int    $product_id WooCommerce product ID
     * @param string $variant_id Printify variant ID
     * @param string $image_url  Image URL
     * @return int|WP_Error Attachment ID or error
     */
    public function import_variant_image($product_id, $variant_id, $image_url);
}
