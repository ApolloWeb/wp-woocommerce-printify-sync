<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;

/**
 * Product Sync Service
 * 
 * Handles syncing products between Printify and WooCommerce
 */
class ProductSyncService {
    /**
     * @var PrintifyApiClient
     */
    private $api;
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct(PrintifyApiClient $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
        
        // Register hooks
        add_action('wp_ajax_wpwps_import_product', [$this, 'importProductAjax']);
        add_action('wp_ajax_wpwps_sync_product', [$this, 'syncProductAjax']);
        add_action('wpwps_sync_products', [$this, 'syncAllProducts']);
    }
    
    /**
     * Import a product from Printify to WooCommerce
     *
     * @param string $printify_id Printify product ID
     * @return int WooCommerce product ID
     */
    public function importProduct(string $printify_id): int {
        $this->logger->log("Importing product {$printify_id} from Printify", 'info');
        
        try {
            // Get product from Printify
            $printify_product = $this->api->getProduct($printify_id);
            
            // Create or update WooCommerce product
            $product_id = $this->createOrUpdateWooProduct($printify_product);
            
            $this->logger->log("Product {$printify_id} imported successfully as WooCommerce product #{$product_id}", 'info');
            
            return $product_id;
        } catch (\Exception $e) {
            $this->logger->log("Error importing product {$printify_id}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * AJAX handler for importing a product
     */
    public function importProductAjax(): void {
        check_ajax_referer('wpps_admin');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
        }
        
        $printify_id = isset($_POST['printify_id']) ? sanitize_text_field($_POST['printify_id']) : '';
        
        if (empty($printify_id)) {
            wp_send_json_error(['message' => __('Product ID is required', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        try {
            $product_id = $this->importProduct($printify_id);
            wp_send_json_success([
                'message' => __('Product imported successfully', 'wp-woocommerce-printify-sync'),
                'product_id' => $product_id
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Sync a WooCommerce product with Printify
     *
     * @param int $product_id WooCommerce product ID
     * @return bool Success
     */
    public function syncProduct(int $product_id): bool {
        $this->logger->log("Syncing WooCommerce product #{$product_id} to Printify", 'info');
        
        try {
            // Get WooCommerce product
            $product = wc_get_product($product_id);
            
            if (!$product) {
                throw new \Exception("Product #{$product_id} not found");
            }
            
            // Get Printify ID
            $printify_id = get_post_meta($product_id, '_printify_product_id', true);
            
            if (empty($printify_id)) {
                throw new \Exception("Product #{$product_id} is not linked to Printify");
            }
            
            // Prepare data for Printify
            $printify_data = $this->prepareProductData($product);
            
            // Update product on Printify
            $this->api->updateProduct($printify_id, $printify_data);
            
            $this->logger->log("Product #{$product_id} synced successfully to Printify", 'info');
            
            return true;
        } catch (\Exception $e) {
            $this->logger->log("Error syncing product #{$product_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * AJAX handler for syncing a product
     */
    public function syncProductAjax(): void {
        check_ajax_referer('wpps_admin');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
        }
        
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        
        if (empty($product_id)) {
            wp_send_json_error(['message' => __('Product ID is required', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        try {
            $success = $this->syncProduct($product_id);
            
            if ($success) {
                wp_send_json_success([
                    'message' => __('Product synced successfully', 'wp-woocommerce-printify-sync')
                ]);
            } else {
                wp_send_json_error(['message' => __('Failed to sync product', 'wp-woocommerce-printify-sync')]);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Sync all products from Printify to WooCommerce
     */
    public function syncAllProducts(): void {
        $this->logger->log("Starting bulk product sync from Printify", 'info');
        
        try {
            $page = 1;
            $limit = 20;
            $imported = 0;
            $failed = 0;
            
            do {
                $response = $this->api->getProducts($page, $limit);
                $products = $response['data'] ?? [];
                
                foreach ($products as $printify_product) {
                    try {
                        $this->createOrUpdateWooProduct($printify_product);
                        $imported++;
                    } catch (\Exception $e) {
                        $this->logger->log("Error importing product {$printify_product['id']}: " . $e->getMessage(), 'error');
                        $failed++;
                    }
                }
                
                $page++;
            } while (!empty($products));
            
            $this->logger->log("Bulk product sync completed. Imported: {$imported}, Failed: {$failed}", 'info');
        } catch (\Exception $e) {
            $this->logger->log("Error in bulk product sync: " . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Create or update a WooCommerce product from Printify data
     *
     * @param array $printify_product Printify product data
     * @return int WooCommerce product ID
     */
    private function createOrUpdateWooProduct(array $printify_product): int {
        // Check if product already exists
        $args = [
            'post_type' => 'product',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'value' => $printify_product['id']
                ]
            ]
        ];
        
        $existing = get_posts($args);
        $product_id = !empty($existing) ? $existing[0]->ID : 0;
        
        // Prepare product data
        $product_data = [
            'name' => $printify_product['title'],
            'description' => $printify_product['description'] ?? '',
            'short_description' => $printify_product['short_description'] ?? '',
            'status' => 'publish',
            'catalog_visibility' => 'visible',
            'regular_price' => $this->getPrintifyPrice($printify_product),
            'virtual' => false,
            'downloadable' => false,
            'tax_status' => 'taxable',
            'manage_stock' => false,
        ];
        
        // Create or update the product
        if ($product_id) {
            $product = wc_get_product($product_id);
            $product->set_name($product_data['name']);
            $product->set_description($product_data['description']);
            $product->set_short_description($product_data['short_description']);
            $product->set_regular_price($product_data['regular_price']);
            $product->save();
        } else {
            $product = new \WC_Product();
            $product->set_name($product_data['name']);
            $product->set_description($product_data['description']);
            $product->set_short_description($product_data['short_description']);
            $product->set_regular_price($product_data['regular_price']);
            $product->set_status('publish');
            $product_id = $product->save();
            
            // Set the Printify product ID
            update_post_meta($product_id, '_printify_product_id', $printify_product['id']);
        }
        
        // Process images
        $this->processProductImages($product_id, $printify_product);
        
        // Process variants if this is a variable product
        if (!empty($printify_product['variants'])) {
            $this->processProductVariants($product_id, $printify_product);
        }
        
        return $product_id;
    }
    
    /**
     * Get price from Printify product
     *
     * @param array $printify_product Printify product data
     * @return string Price
     */
    private function getPrintifyPrice(array $printify_product): string {
        if (!empty($printify_product['variants'])) {
            // For variable products, get the base price from the first variant
            return isset($printify_product['variants'][0]['price']) ? 
                   number_format($printify_product['variants'][0]['price'] / 100, 2) : 
                   '0.00';
        }
        
        return isset($printify_product['price']) ? 
               number_format($printify_product['price'] / 100, 2) : 
               '0.00';
    }
    
    /**
     * Process product images
     *
     * @param int $product_id WooCommerce product ID
     * @param array $printify_product Printify product data
     */
    private function processProductImages(int $product_id, array $printify_product): void {
        if (empty($printify_product['images'])) {
            return;
        }
        
        $product = wc_get_product($product_id);
        $image_ids = [];
        
        foreach ($printify_product['images'] as $index => $image) {
            $image_url = $image['src'];
            $image_id = $this->uploadImageFromUrl($image_url, $product_id);
            
            if ($image_id) {
                $image_ids[] = $image_id;
                
                // Set first image as product thumbnail
                if ($index === 0) {
                    $product->set_image_id($image_id);
                }
            }
        }
        
        // Set product gallery
        if (count($image_ids) > 1) {
            // Remove the first image which is already the product thumbnail
            array_shift($image_ids);
            $product->set_gallery_image_ids($image_ids);
        }
        
        $product->save();
    }
    
    /**
     * Process product variants
     *
     * @param int $product_id WooCommerce product ID
     * @param array $printify_product Printify product data
     */
    private function processProductVariants(int $product_id, array $printify_product): void {
        // Implementation for processing product variants would go here
        // This would involve:
        // 1. Creating product attributes based on Printify options
        // 2. Setting up variation combinations
        // 3. Setting prices, stock, etc. for each variation
        $this->logger->log("Processing variants for product #{$product_id} skipped - feature to be implemented", 'debug');
    }
    
    /**
     * Prepare product data for Printify update
     *
     * @param \WC_Product $product WooCommerce product
     * @return array Printify product data
     */
    private function prepareProductData(\WC_Product $product): array {
        return [
            'title' => $product->get_name(),
            'description' => $product->get_description(),
            'tags' => $this->getProductTags($product),
            'options' => [],  // Would include product attributes
            'variants' => []  // Would include variant data
        ];
    }
    
    /**
     * Get product tags
     *
     * @param \WC_Product $product WooCommerce product
     * @return array Tags
     */
    private function getProductTags(\WC_Product $product): array {
        $terms = get_the_terms($product->get_id(), 'product_tag');
        $tags = [];
        
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $tags[] = $term->name;
            }
        }
        
        return $tags;
    }
    
    /**
     * Upload image from URL
     *
     * @param string $url Image URL
     * @param int $product_id WooCommerce product ID
     * @return int|false Attachment ID or false on failure
     */
    private function uploadImageFromUrl(string $url, int $product_id) {
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($url);
        
        if (!$image_data) {
            return false;
        }
        
        $filename = basename($url);
        
        // Check file type
        $filetype = wp_check_filetype($filename, null);
        if (!$filetype['type']) {
            return false;
        }
        
        // Create the file in the upload directory
        $file = $upload_dir['path'] . '/' . $filename;
        file_put_contents($file, $image_data);
        
        // Create attachment
        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        
        $attachment_id = wp_insert_attachment($attachment, $file, $product_id);
        
        if (!$attachment_id) {
            return false;
        }
        
        // Generate metadata and update the attachment
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        return $attachment_id;
    }
    
    /**
     * Sync product stock to Printify
     *
     * @param int $product_id WooCommerce product ID
     * @return bool Success
     */
    public function syncProductStock(int $product_id): bool {
        $this->logger->log("Syncing stock for product #{$product_id} to Printify", 'info');
        
        try {
            $product = wc_get_product($product_id);
            
            if (!$product) {
                throw new \Exception("Product #{$product_id} not found");
            }
            
            $printify_id = get_post_meta($product_id, '_printify_product_id', true);
            
            if (empty($printify_id)) {
                throw new \Exception("Product #{$product_id} is not linked to Printify");
            }
            
            // For simple products
            if ($product->is_type('simple')) {
                $stock_data = [
                    'stock' => $product->get_stock_quantity(),
                    'backorder' => $product->get_backorders() !== 'no'
                ];
                
                $this->api->updateProductStock($printify_id, $stock_data);
            } 
            // For variable products
            else if ($product->is_type('variable')) {
                $variations = $product->get_children();
                $stock_data = [];
                
                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    $printify_variant_id = get_post_meta($variation_id, '_printify_variant_id', true);
                    
                    if ($variation && $printify_variant_id) {
                        $stock_data[$printify_variant_id] = [
                            'stock' => $variation->get_stock_quantity(),
                            'backorder' => $variation->get_backorders() !== 'no'
                        ];
                    }
                }
                
                if (!empty($stock_data)) {
                    $this->api->updateProductVariantStock($printify_id, $stock_data);
                }
            }
            
            $this->logger->log("Stock for product #{$product_id} synced successfully to Printify", 'info');
            return true;
        } catch (\Exception $e) {
            $this->logger->log("Error syncing stock for product #{$product_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Sync product images to Printify
     *
     * @param int $product_id WooCommerce product ID
     * @return bool Success
     */
    public function syncProductImages(int $product_id): bool {
        $this->logger->log("Syncing images for product #{$product_id} to Printify", 'info');
        
        try {
            $product = wc_get_product($product_id);
            
            if (!$product) {
                throw new \Exception("Product #{$product_id} not found");
            }
            
            $printify_id = get_post_meta($product_id, '_printify_product_id', true);
            
            if (empty($printify_id)) {
                throw new \Exception("Product #{$product_id} is not linked to Printify");
            }
            
            $images = [];
            
            // Get main image
            $image_id = $product->get_image_id();
            if ($image_id) {
                $image_url = wp_get_attachment_url($image_id);
                if ($image_url) {
                    $images[] = ['src' => $image_url];
                }
            }
            
            // Get gallery images
            $gallery_ids = $product->get_gallery_image_ids();
            foreach ($gallery_ids as $gallery_id) {
                $gallery_url = wp_get_attachment_url($gallery_id);
                if ($gallery_url) {
                    $images[] = ['src' => $gallery_url];
                }
            }
            
            if (empty($images)) {
                $this->logger->log("No images found for product #{$product_id}", 'warning');
                return false;
            }
            
            // Send images to Printify
            $this->api->updateProductImages($printify_id, $images);
            
            $this->logger->log("Images for product #{$product_id} synced successfully to Printify", 'info');
            return true;
        } catch (\Exception $e) {
            $this->logger->log("Error syncing images for product #{$product_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Publish a product on Printify
     *
     * @param string $printify_id Printify product ID
     * @return bool Success
     */
    public function publishPrintifyProduct(string $printify_id): bool {
        $this->logger->log("Publishing Printify product {$printify_id}", 'info');
        
        try {
            $this->api->publishProduct($printify_id);
            $this->logger->log("Printify product {$printify_id} published successfully", 'info');
            return true;
        } catch (\Exception $e) {
            $this->logger->log("Error publishing Printify product {$printify_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Unpublish a product on Printify
     *
     * @param string $printify_id Printify product ID
     * @return bool Success
     */
    public function unpublishPrintifyProduct(string $printify_id): bool {
        $this->logger->log("Unpublishing Printify product {$printify_id}", 'info');
        
        try {
            // Note: This might require setting product status to draft in Printify API
            $this->api->updateProductVisibility($printify_id, false);
            $this->logger->log("Printify product {$printify_id} unpublished successfully", 'info');
            return true;
        } catch (\Exception $e) {
            $this->logger->log("Error unpublishing Printify product {$printify_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
