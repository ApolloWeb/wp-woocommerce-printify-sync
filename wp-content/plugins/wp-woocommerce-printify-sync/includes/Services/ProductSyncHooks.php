<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;

/**
 * Product Sync Hooks Service
 * 
 * Registers all WooCommerce hooks for product synchronization with Printify
 */
class ProductSyncHooks {
    /**
     * @var PrintifyApiClient
     */
    private $api;
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var ProductSyncService
     */
    private $product_sync;
    
    /**
     * Constructor
     */
    public function __construct(PrintifyApiClient $api, Logger $logger, ProductSyncService $product_sync) {
        $this->api = $api;
        $this->logger = $logger;
        $this->product_sync = $product_sync;
    }
    
    /**
     * Initialize hooks
     */
    public function init(): void {
        // Product CRUD hooks
        add_action('woocommerce_update_product', [$this, 'handleProductUpdate'], 10, 2);
        add_action('woocommerce_product_quick_edit_save', [$this, 'handleQuickEditSave']);
        add_action('woocommerce_admin_process_product_object', [$this, 'handleProductSave']);
        add_action('woocommerce_new_product', [$this, 'handleNewProduct'], 10);
        add_action('woocommerce_update_product_variation', [$this, 'handleVariationUpdate'], 10, 2);
        add_action('woocommerce_save_product_variation', [$this, 'handleVariationSave'], 10, 2);
        
        // Product inventory hooks
        add_action('woocommerce_product_set_stock', [$this, 'handleStockChange']);
        add_action('woocommerce_variation_set_stock', [$this, 'handleVariationStockChange']);
        
        // Product deletion hooks
        add_action('before_delete_post', [$this, 'handleProductDeletion']);
        add_action('wp_trash_post', [$this, 'handleProductTrash']);
        add_action('untrash_post', [$this, 'handleProductUntrash']);
        
        // Product category/tag hooks
        add_action('edited_product_cat', [$this, 'handleCategoryUpdate']);
        add_action('edited_product_tag', [$this, 'handleTagUpdate']);
        
        // Product image hooks
        add_action('woocommerce_product_set_image', [$this, 'handleImageChange'], 10, 2);
        add_action('woocommerce_product_set_gallery', [$this, 'handleGalleryChange'], 10, 2);
        
        // Bulk edit hooks
        add_action('woocommerce_product_bulk_edit_save', [$this, 'handleBulkProductUpdate']);
        
        // Admin AJAX hooks
        add_action('wp_ajax_wpwps_import_product', [$this->product_sync, 'importProductAjax']);
        add_action('wp_ajax_wpwps_sync_product', [$this->product_sync, 'syncProductAjax']);
        add_action('wp_ajax_wpwps_bulk_sync_products', [$this, 'bulkSyncProductsAjax']);
    }
    
    /**
     * Handle product update
     * 
     * @param int $product_id Product ID
     * @param \WC_Product $product Product object
     */
    public function handleProductUpdate(int $product_id, \WC_Product $product): void {
        // Skip if product is not linked to Printify
        if (!$this->isLinkedToPrintify($product_id)) {
            return;
        }
        
        // Check if auto-sync is enabled
        if ($this->isAutoSyncEnabled()) {
            $this->logger->log("Auto-syncing product #{$product_id} to Printify after update", 'info');
            $this->product_sync->syncProduct($product_id);
        } else {
            $this->logger->log("Product #{$product_id} updated but auto-sync is disabled", 'info');
            // Mark product as needing sync
            update_post_meta($product_id, '_printify_needs_sync', '1');
        }
    }
    
    /**
     * Handle quick edit save
     * 
     * @param \WC_Product $product Product object
     */
    public function handleQuickEditSave(\WC_Product $product): void {
        $product_id = $product->get_id();
        
        // Skip if product is not linked to Printify
        if (!$this->isLinkedToPrintify($product_id)) {
            return;
        }
        
        $this->logger->log("Product #{$product_id} quick-edited", 'info');
        
        if ($this->isAutoSyncEnabled()) {
            $this->product_sync->syncProduct($product_id);
        } else {
            update_post_meta($product_id, '_printify_needs_sync', '1');
        }
    }
    
    /**
     * Handle product object save
     * 
     * @param \WC_Product $product Product object
     */
    public function handleProductSave(\WC_Product $product): void {
        // This is called during product save in the admin
        // We'll let the woocommerce_update_product hook handle the actual sync
    }
    
    /**
     * Handle new product creation
     * 
     * @param int $product_id Product ID
     */
    public function handleNewProduct(int $product_id): void {
        // New products created in WooCommerce are not synced to Printify automatically
        // This is a manual process initiated from the admin UI
        // But we'll log it for debugging purposes
        $this->logger->log("New product #{$product_id} created in WooCommerce", 'debug');
    }
    
    /**
     * Handle variation update
     * 
     * @param int $variation_id Variation ID
     * @param int $variation_position Variation position
     */
    public function handleVariationUpdate(int $variation_id, int $variation_position): void {
        $variation = wc_get_product($variation_id);
        
        if (!$variation) {
            return;
        }
        
        $parent_id = $variation->get_parent_id();
        
        // Skip if parent product is not linked to Printify
        if (!$this->isLinkedToPrintify($parent_id)) {
            return;
        }
        
        $this->logger->log("Product variation #{$variation_id} updated", 'debug');
        
        if ($this->isAutoSyncEnabled()) {
            // For variations, we sync the parent product
            $this->product_sync->syncProduct($parent_id);
        } else {
            update_post_meta($parent_id, '_printify_needs_sync', '1');
        }
    }
    
    /**
     * Handle variation save
     * 
     * @param int $variation_id Variation ID
     * @param int $i Loop index
     */
    public function handleVariationSave(int $variation_id, int $i): void {
        // We'll let the woocommerce_update_product_variation hook handle this
    }
    
    /**
     * Handle stock change
     * 
     * @param \WC_Product $product Product object
     */
    public function handleStockChange(\WC_Product $product): void {
        $product_id = $product->get_id();
        
        // Skip if product is not linked to Printify
        if (!$this->isLinkedToPrintify($product_id)) {
            return;
        }
        
        $this->logger->log("Stock changed for product #{$product_id}", 'debug');
        
        // Check if stock sync is enabled (separate setting from general auto-sync)
        $stock_sync = get_option('wpwps_sync_stock', 'no');
        
        if ($stock_sync === 'yes' && $this->isAutoSyncEnabled()) {
            $this->product_sync->syncProductStock($product_id);
        }
    }
    
    /**
     * Handle variation stock change
     * 
     * @param \WC_Product_Variation $variation Variation object
     */
    public function handleVariationStockChange(\WC_Product_Variation $variation): void {
        $parent_id = $variation->get_parent_id();
        
        // Skip if parent product is not linked to Printify
        if (!$this->isLinkedToPrintify($parent_id)) {
            return;
        }
        
        $this->logger->log("Stock changed for variation #{$variation->get_id()}", 'debug');
        
        // Check if stock sync is enabled
        $stock_sync = get_option('wpwps_sync_stock', 'no');
        
        if ($stock_sync === 'yes' && $this->isAutoSyncEnabled()) {
            $this->product_sync->syncProductStock($parent_id);
        }
    }
    
    /**
     * Handle product deletion
     * 
     * @param int $post_id Post ID
     */
    public function handleProductDeletion(int $post_id): void {
        // Skip if not a product
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        // Skip if product is not linked to Printify
        if (!$this->isLinkedToPrintify($post_id)) {
            return;
        }
        
        $printify_id = get_post_meta($post_id, '_printify_product_id', true);
        $this->logger->log("Product #{$post_id} with Printify ID {$printify_id} is being deleted", 'warning');
        
        // We don't automatically delete from Printify, but could implement as an option
    }
    
    /**
     * Handle product being trashed
     * 
     * @param int $post_id Post ID
     */
    public function handleProductTrash(int $post_id): void {
        // Skip if not a product
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        // Skip if product is not linked to Printify
        if (!$this->isLinkedToPrintify($post_id)) {
            return;
        }
        
        $printify_id = get_post_meta($post_id, '_printify_product_id', true);
        $this->logger->log("Product #{$post_id} with Printify ID {$printify_id} is being trashed", 'info');
        
        // Option: Update product status in Printify to draft/hidden
        if (get_option('wpwps_unpublish_on_trash', 'no') === 'yes' && $this->isAutoSyncEnabled()) {
            $this->product_sync->unpublishPrintifyProduct($printify_id);
        }
    }
    
    /**
     * Handle product being restored from trash
     * 
     * @param int $post_id Post ID
     */
    public function handleProductUntrash(int $post_id): void {
        // Skip if not a product
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        // Skip if product is not linked to Printify
        if (!$this->isLinkedToPrintify($post_id)) {
            return;
        }
        
        $printify_id = get_post_meta($post_id, '_printify_product_id', true);
        $this->logger->log("Product #{$post_id} with Printify ID {$printify_id} is being restored from trash", 'info');
        
        // Option: Update product status in Printify to published
        if (get_option('wpwps_publish_on_untrash', 'no') === 'yes' && $this->isAutoSyncEnabled()) {
            $this->product_sync->publishPrintifyProduct($printify_id);
        }
    }
    
    /**
     * Handle product category update
     * 
     * @param int $term_id Term ID
     */
    public function handleCategoryUpdate(int $term_id): void {
        // Find all Printify-linked products in this category and update them
        $products = $this->getProductsByTerm($term_id, 'product_cat');
        
        if (empty($products)) {
            return;
        }
        
        $this->logger->log("Category #{$term_id} updated, affecting " . count($products) . " Printify products", 'info');
        
        // Update products if auto-sync is enabled
        if ($this->isAutoSyncEnabled()) {
            foreach ($products as $product_id) {
                $this->product_sync->syncProduct($product_id);
            }
        }
    }
    
    /**
     * Handle product tag update
     * 
     * @param int $term_id Term ID
     */
    public function handleTagUpdate(int $term_id): void {
        // Find all Printify-linked products with this tag and update them
        $products = $this->getProductsByTerm($term_id, 'product_tag');
        
        if (empty($products)) {
            return;
        }
        
        $this->logger->log("Tag #{$term_id} updated, affecting " . count($products) . " Printify products", 'info');
        
        // Update products if auto-sync is enabled
        if ($this->isAutoSyncEnabled()) {
            foreach ($products as $product_id) {
                $this->product_sync->syncProduct($product_id);
            }
        }
    }
    
    /**
     * Handle product image change
     * 
     * @param int $product_id Product ID
     * @param int $image_id Image ID
     */
    public function handleImageChange(int $product_id, int $image_id): void {
        // Skip if product is not linked to Printify
        if (!$this->isLinkedToPrintify($product_id)) {
            return;
        }
        
        $this->logger->log("Featured image changed for product #{$product_id}", 'debug');
        
        // Check if image sync is enabled
        $image_sync = get_option('wpwps_sync_images', 'no');
        
        if ($image_sync === 'yes' && $this->isAutoSyncEnabled()) {
            $this->product_sync->syncProductImages($product_id);
        }
    }
    
    /**
     * Handle product gallery change
     * 
     * @param int $product_id Product ID
     * @param array $image_ids Image IDs
     */
    public function handleGalleryChange(int $product_id, array $image_ids): void {
        // Skip if product is not linked to Printify
        if (!$this->isLinkedToPrintify($product_id)) {
            return;
        }
        
        $this->logger->log("Gallery images changed for product #{$product_id}", 'debug');
        
        // Check if image sync is enabled
        $image_sync = get_option('wpwps_sync_images', 'no');
        
        if ($image_sync === 'yes' && $this->isAutoSyncEnabled()) {
            $this->product_sync->syncProductImages($product_id);
        }
    }
    
    /**
     * Handle bulk product update
     * 
     * @param \WC_Product $product Product object
     */
    public function handleBulkProductUpdate(\WC_Product $product): void {
        $product_id = $product->get_id();
        
        // Skip if product is not linked to Printify
        if (!$this->isLinkedToPrintify($product_id)) {
            return;
        }
        
        $this->logger->log("Product #{$product_id} updated via bulk edit", 'info');
        
        if ($this->isAutoSyncEnabled()) {
            $this->product_sync->syncProduct($product_id);
        } else {
            update_post_meta($product_id, '_printify_needs_sync', '1');
        }
    }
    
    /**
     * AJAX handler for bulk syncing products
     */
    public function bulkSyncProductsAjax(): void {
        check_ajax_referer('wpps_admin');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
        }
        
        $product_ids = isset($_POST['product_ids']) ? array_map('absint', (array)$_POST['product_ids']) : [];
        
        if (empty($product_ids)) {
            wp_send_json_error(['message' => __('No products selected', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $synced = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($product_ids as $product_id) {
            if (!$this->isLinkedToPrintify($product_id)) {
                $failed++;
                $errors[] = sprintf(__('Product #%d is not linked to Printify', 'wp-woocommerce-printify-sync'), $product_id);
                continue;
            }
            
            try {
                $result = $this->product_sync->syncProduct($product_id);
                if ($result) {
                    $synced++;
                } else {
                    $failed++;
                    $errors[] = sprintf(__('Failed to sync product #%d', 'wp-woocommerce-printify-sync'), $product_id);
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = sprintf(__('Error syncing product #%d: %s', 'wp-woocommerce-printify-sync'), $product_id, $e->getMessage());
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Synced %d products, %d failed', 'wp-woocommerce-printify-sync'), $synced, $failed),
            'synced' => $synced,
            'failed' => $failed,
            'errors' => $errors
        ]);
    }
    
    /**
     * Check if product is linked to Printify
     * 
     * @param int $product_id Product ID
     * @return bool Is linked
     */
    private function isLinkedToPrintify(int $product_id): bool {
        $printify_id = get_post_meta($product_id, '_printify_product_id', true);
        return !empty($printify_id);
    }
    
    /**
     * Check if auto-sync is enabled
     * 
     * @return bool Is enabled
     */
    private function isAutoSyncEnabled(): bool {
        return get_option('wpwps_auto_sync_products', 'no') === 'yes';
    }
    
    /**
     * Get products by term
     * 
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy
     * @return array Product IDs
     */
    private function getProductsByTerm(int $term_id, string $taxonomy): array {
        $products = wc_get_products([
            'limit' => -1,
            'return' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term_id
                ]
            ],
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        return $products;
    }
}
