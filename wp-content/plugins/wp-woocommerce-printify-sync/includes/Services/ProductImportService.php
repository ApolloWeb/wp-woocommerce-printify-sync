<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;

/**
 * Handles importing and updating products from Printify to WooCommerce
 */
class ProductImportService {
    /**
     * @var PrintifyApiClient
     */
    private $api;
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var array Mapping of Printify product types to WooCommerce categories
     */
    private $category_mappings = [
        'T-shirt' => ['Apparel', 'T-Shirts'],
        'Hoodie' => ['Apparel', 'Hoodies'],
        'Sweatshirt' => ['Apparel', 'Sweatshirts'],
        'Tank top' => ['Apparel', 'Tank Tops'],
        'Poster' => ['Wall Art', 'Posters'],
        'Canvas' => ['Wall Art', 'Canvas'],
        'Mug' => ['Home & Living', 'Mugs'],
        'Phone case' => ['Accessories', 'Phone Cases'],
        // Add more mappings as needed
    ];
    
    /**
     * Constructor
     */
    public function __construct(PrintifyApiClient $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
        
        // Register hooks
        add_action('wpwps_import_product', [$this, 'importProduct'], 10, 2);
        add_action('wpwps_bulk_import_products', [$this, 'bulkImportProducts']);
        add_action('wpwps_update_product', [$this, 'updateProduct'], 10, 2);
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_import_printify_product', [$this, 'ajaxImportProduct']);
        add_action('wp_ajax_wpwps_bulk_import_printify_products', [$this, 'ajaxBulkImportProducts']);
    }
    
    /**
     * Schedule all products to be imported from Printify
     */
    public function scheduleBulkImport(): void {
        // Schedule the bulk import task using Action Scheduler
        if (!as_next_scheduled_action('wpwps_bulk_import_products')) {
            as_schedule_single_action(time(), 'wpwps_bulk_import_products');
            $this->logger->log('Scheduled bulk import of Printify products', 'info');
        }
    }
    
    /**
     * Bulk import products from Printify
     */
    public function bulkImportProducts(): void {
        $this->logger->log('Starting bulk import of Printify products', 'info');
        
        try {
            $page = 1;
            $per_page = 20;
            $imported = 0;
            $failed = 0;
            
            do {
                // Get products from Printify
                $products = $this->api->getProducts($page, $per_page);
                
                if (empty($products['data'])) {
                    break;
                }
                
                foreach ($products['data'] as $product) {
                    // Schedule individual product import with 5 second delay between each
                    // to avoid overwhelming the server
                    $timestamp = time() + ($imported * 5);
                    as_schedule_single_action($timestamp, 'wpwps_import_product', [
                        'printify_id' => $product['id'],
                        'create_if_not_exists' => true
                    ]);
                    
                    $imported++;
                }
                
                $page++;
            } while (!empty($products['data']) && count($products['data']) === $per_page);
            
            $this->logger->log("Scheduled import of {$imported} Printify products", 'info');
        } catch (\Exception $e) {
            $this->logger->log('Error scheduling bulk import: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Import a single product from Printify
     * 
     * @param string $printify_id Printify product ID
     * @param bool $create_if_not_exists Create product if it doesn't exist
     * @return int|false WooCommerce product ID or false on failure
     */
    public function importProduct(string $printify_id, bool $create_if_not_exists = true) {
        $this->logger->log("Importing Printify product: {$printify_id}", 'info');
        
        try {
            // Check if product already exists in WooCommerce
            $product_id = $this->getWooProductIdByPrintifyId($printify_id);
            
            // If product exists, update it unless instructed otherwise
            if ($product_id) {
                $this->logger->log("Printify product {$printify_id} already exists as WooCommerce product #{$product_id}", 'info');
                return $this->updateProduct($printify_id, $product_id);
            }
            
            // If product doesn't exist and we're not supposed to create it, return false
            if (!$create_if_not_exists) {
                $this->logger->log("Printify product {$printify_id} doesn't exist and create_if_not_exists is false", 'info');
                return false;
            }
            
            // Get detailed product data from Printify
            $printify_product = $this->api->getProduct($printify_id);
            
            // Create product in WooCommerce
            $product_id = $this->createWooProduct($printify_product);
            
            $this->logger->log("Successfully imported Printify product {$printify_id} as WooCommerce product #{$product_id}", 'info');
            
            return $product_id;
        } catch (\Exception $e) {
            $this->logger->log("Error importing Printify product {$printify_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Update an existing WooCommerce product with data from Printify
     * 
     * @param string $printify_id Printify product ID
     * @param int $product_id WooCommerce product ID
     * @return int|false WooCommerce product ID or false on failure
     */
    public function updateProduct(string $printify_id, int $product_id) {
        $this->logger->log("Updating WooCommerce product #{$product_id} with Printify data", 'info');
        
        try {
            // Get detailed product data from Printify
            $printify_product = $this->api->getProduct($printify_id);
            
            // Get WooCommerce product
            $product = wc_get_product($product_id);
            
            if (!$product) {
                throw new \Exception("WooCommerce product #{$product_id} not found");
            }
            
            // Update product data
            $this->updateWooProduct($product, $printify_product);
            
            $this->logger->log("Successfully updated WooCommerce product #{$product_id} with Printify data", 'info');
            
            return $product_id;
        } catch (\Exception $e) {
            $this->logger->log("Error updating WooCommerce product #{$product_id}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * AJAX handler for importing a product
     */
    public function ajaxImportProduct(): void {
        check_ajax_referer('wpps_admin');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
        }
        
        $printify_id = isset($_POST['printify_id']) ? sanitize_text_field($_POST['printify_id']) : '';
        
        if (empty($printify_id)) {
            wp_send_json_error(['message' => __('Printify product ID is required', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        $product_id = $this->importProduct($printify_id);
        
        if ($product_id) {
            wp_send_json_success([
                'message' => __('Product imported successfully', 'wp-woocommerce-printify-sync'),
                'product_id' => $product_id,
                'edit_url' => get_edit_post_link($product_id, 'raw')
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to import product', 'wp-woocommerce-printify-sync')]);
        }
    }
    
    /**
     * AJAX handler for bulk importing products
     */
    public function ajaxBulkImportProducts(): void {
        check_ajax_referer('wpps_admin');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')], 403);
        }
        
        $this->scheduleBulkImport();
        
        wp_send_json_success([
            'message' => __('Bulk import scheduled', 'wp-woocommerce-printify-sync')
        ]);
    }
    
    /**
     * Create a WooCommerce product from Printify data
     * 
     * @param array $printify_product Printify product data
     * @return int WooCommerce product ID
     */
    private function createWooProduct(array $printify_product): int {
        // Determine product type - if it has variants, it's a variable product
        $product_type = !empty($printify_product['variants']) && count($printify_product['variants']) > 1 
            ? 'variable' : 'simple';
        
        // Create the product
        $product = new \WC_Product_Variable();  // Default to variable, we'll change it if needed
        
        // Basic product data
        $product->set_name($printify_product['title']);
        $product->set_description($printify_product['description'] ?? '');
        $product->set_status('publish');
        
        // Save to get an ID
        $product_id = $product->save();
        
        // Store Printify metadata
        update_post_meta($product_id, '_printify_product_id', $printify_product['id']);
        update_post_meta($product_id, '_printify_provider_id', $printify_product['print_provider_id'] ?? '');
        update_post_meta($product_id, '_printify_blueprint_id', $printify_product['blueprint_id'] ?? '');
        update_post_meta($product_id, '_printify_last_synced', current_time('mysql'));
        
        // Process categories based on product type
        $this->processCategories($product_id, $printify_product);
        
        // Process tags
        $this->processTags($product_id, $printify_product);
        
        // Process images
        $this->processImages($product_id, $printify_product);
        
        // Process variants
        if ($product_type === 'variable') {
            $this->processVariations($product_id, $printify_product);
        } else {
            // Convert to simple product
            $product = new \WC_Product_Simple($product_id);
            
            // Set simple product data
            if (!empty($printify_product['variants'])) {
                $variant = $printify_product['variants'][0];
                $product->set_regular_price($variant['price'] / 100); // Printify prices are in cents
                $product->set_sku($variant['sku'] ?? '');
                
                // Store cost price
                update_post_meta($product_id, '_printify_cost_price', $variant['cost'] / 100);
            }
            
            $product->save();
        }
        
        // Push product ID back to Printify if enabled
        if (get_option('wpwps_sync_external_id', 'yes') === 'yes') {
            $this->updatePrintifyExternalId($printify_product['id'], $product_id);
        }
        
        return $product_id;
    }
    
    /**
     * Update a WooCommerce product with Printify data
     * 
     * @param \WC_Product $product WooCommerce product object
     * @param array $printify_product Printify product data
     * @return bool Success
     */
    private function updateWooProduct(\WC_Product $product, array $printify_product): bool {
        $product_id = $product->get_id();
        
        // Update basic product data
        $product->set_name($printify_product['title']);
        $product->set_description($printify_product['description'] ?? '');
        
        // Update Printify metadata
        update_post_meta($product_id, '_printify_provider_id', $printify_product['print_provider_id'] ?? '');
        update_post_meta($product_id, '_printify_blueprint_id', $printify_product['blueprint_id'] ?? '');
        update_post_meta($product_id, '_printify_last_synced', current_time('mysql'));
        
        // Process categories
        $this->processCategories($product_id, $printify_product);
        
        // Process tags
        $this->processTags($product_id, $printify_product);
        
        // Process images
        $this->processImages($product_id, $printify_product);
        
        // Process variants
        if ($product->is_type('variable') && !empty($printify_product['variants']) && count($printify_product['variants']) > 1) {
            $this->processVariations($product_id, $printify_product);
        } else if (!empty($printify_product['variants'])) {
            // Update simple product data
            $variant = $printify_product['variants'][0];
            $product->set_regular_price($variant['price'] / 100);
            $product->set_sku($variant['sku'] ?? '');
            
            // Store cost price
            update_post_meta($product_id, '_printify_cost_price', $variant['cost'] / 100);
            
            // Update stock if applicable
            if (isset($variant['is_enabled']) && !$variant['is_enabled']) {
                $product->set_stock_status('outofstock');
            } else {
                $product->set_stock_status('instock');
            }
        }
        
        $product->save();
        
        return true;
    }
    
    /**
     * Process product categories
     * 
     * @param int $product_id WooCommerce product ID
     * @param array $printify_product Printify product data
     */
    private function processCategories(int $product_id, array $printify_product): void {
        // Get product type from Printify
        $product_type = $printify_product['print_provider']['title'] ?? '';
        $blueprint_name = $printify_product['blueprint_name'] ?? '';
        
        $category_path = [];
        
        // Look for mapping based on blueprint name first
        if ($blueprint_name && isset($this->category_mappings[$blueprint_name])) {
            $category_path = $this->category_mappings[$blueprint_name];
        } 
        // Then try to extract from product type
        else if ($product_type) {
            foreach ($this->category_mappings as $type => $path) {
                if (stripos($product_type, $type) !== false || stripos($blueprint_name, $type) !== false) {
                    $category_path = $path;
                    break;
                }
            }
        }
        
        // Default category if no mapping found
        if (empty($category_path)) {
            $category_path = ['Printify', 'Other'];
        }
        
        // Create hierarchical categories and assign to product
        $term_ids = [];
        $parent_id = 0;
        
        foreach ($category_path as $category_name) {
            // Check if category exists
            $term = get_term_by('name', $category_name, 'product_cat');
            
            if ($term) {
                $term_id = $term->term_id;
            } else {
                // Create new category
                $term = wp_insert_term($category_name, 'product_cat', [
                    'parent' => $parent_id
                ]);
                
                if (is_wp_error($term)) {
                    $this->logger->log("Error creating category {$category_name}: " . $term->get_error_message(), 'error');
                    continue;
                }
                
                $term_id = $term['term_id'];
            }
            
            $term_ids[] = $term_id;
            $parent_id = $term_id;
        }
        
        // Assign categories to product
        if (!empty($term_ids)) {
            wp_set_object_terms($product_id, $term_ids, 'product_cat');
        }
    }
    
    /**
     * Process product tags
     * 
     * @param int $product_id WooCommerce product ID
     * @param array $printify_product Printify product data
     */
    private function processTags(int $product_id, array $printify_product): void {
        // Get tags from Printify
        $tags = $printify_product['tags'] ?? [];
        
        if (empty($tags)) {
            return;
        }
        
        // Assign tags to product
        wp_set_object_terms($product_id, $tags, 'product_tag');
    }
    
    /**
     * Process product images
     * 
     * @param int $product_id WooCommerce product ID
     * @param array $printify_product Printify product data
     */
    private function processImages(int $product_id, array $printify_product): void {
        // Get images from Printify
        $images = $printify_product['images'] ?? [];
        
        if (empty($images)) {
            return;
        }
        
        $attachment_ids = [];
        
        // Process each image
        foreach ($images as $index => $image) {
            $image_url = $image['src'];
            $is_variant_image = isset($image['variant_ids']) && !empty($image['variant_ids']);
            
            // Skip variant-specific images for now, we'll handle them in variant processing
            if ($is_variant_image) {
                continue;
            }
            
            // Download and attach image to product
            $attachment_id = $this->downloadAndAttachImage($image_url, $product_id);
            
            if ($attachment_id) {
                $attachment_ids[] = $attachment_id;
                
                // Set first image as product thumbnail
                if ($index === 0) {
                    set_post_thumbnail($product_id, $attachment_id);
                }
            }
        }
        
        // Set product gallery
        if (count($attachment_ids) > 1) {
            // Remove the first image which is already the product thumbnail
            array_shift($attachment_ids);
            update_post_meta($product_id, '_product_image_gallery', implode(',', $attachment_ids));
        }
    }
    
    /**
     * Process product variations
     * 
     * @param int $product_id WooCommerce product ID
     * @param array $printify_product Printify product data
     */
    private function processVariations(int $product_id, array $printify_product): void {
        // Get variants from Printify
        $variants = $printify_product['variants'] ?? [];
        
        if (empty($variants)) {
            return;
        }
        
        // Extract variant attributes
        $attributes = [];
        $variant_ids = [];
        
        foreach ($variants as $variant) {
            $variant_ids[] = $variant['id'];
            
            // Process options
            foreach ($variant['options'] as $option) {
                $name = wc_sanitize_taxonomy_name($option['name']);
                
                if (!isset($attributes[$name])) {
                    $attributes[$name] = [
                        'name' => $option['name'],
                        'values' => []
                    ];
                }
                
                if (!in_array($option['value'], $attributes[$name]['values'])) {
                    $attributes[$name]['values'][] = $option['value'];
                }
            }
        }
        
        // Store the array of Printify variant IDs
        update_post_meta($product_id, '_printify_variant_ids', $variant_ids);
        
        // Create product attributes
        $product_attributes = [];
        
        foreach ($attributes as $key => $attribute) {
            $attribute_id = $this->getAttributeTaxonomyId($attribute['name']);
            
            if (!$attribute_id) {
                // Create attribute if it doesn't exist
                $attribute_id = $this->createAttributeTaxonomy($attribute['name']);
            }
            
            if ($attribute_id) {
                // Attribute is a taxonomy
                $taxonomy = wc_attribute_taxonomy_name_by_id($attribute_id);
                
                // Create terms for attribute values
                $term_ids = [];
                
                foreach ($attribute['values'] as $value) {
                    $term = get_term_by('name', $value, $taxonomy);
                    
                    if (!$term) {
                        $term = wp_insert_term($value, $taxonomy);
                        
                        if (is_wp_error($term)) {
                            continue;
                        }
                        
                        $term_id = $term['term_id'];
                    } else {
                        $term_id = $term->term_id;
                    }
                    
                    $term_ids[] = $term_id;
                }
                
                // Assign terms to product
                wp_set_object_terms($product_id, $term_ids, $taxonomy);
                
                // Add to product attributes array
                $product_attributes[$taxonomy] = [
                    'name' => $taxonomy,
                    'value' => '',
                    'position' => count($product_attributes) + 1,
                    'is_visible' => 1,
                    'is_variation' => 1,
                    'is_taxonomy' => 1
                ];
            } else {
                // Custom product attribute
                $product_attributes["pa_{$key}"] = [
                    'name' => $attribute['name'],
                    'value' => implode('|', $attribute['values']),
                    'position' => count($product_attributes) + 1,
                    'is_visible' => 1,
                    'is_variation' => 1,
                    'is_taxonomy' => 0
                ];
            }
        }
        
        // Save product attributes
        update_post_meta($product_id, '_product_attributes', $product_attributes);
        
        // Load the product again
        $product = wc_get_product($product_id);
        
        // Set low stock threshold
        $low_stock_amount = get_option('woocommerce_notify_low_stock_amount');
        $product->set_low_stock_amount($low_stock_amount);
        $product->save();
        
        // Get existing variations
        $existing_variations = $product->get_children();
        
        // Create or update variations
        foreach ($variants as $variant) {
            // Skip disabled variants if configured to do so
            if (isset($variant['is_enabled']) && !$variant['is_enabled'] && get_option('wpwps_skip_disabled_variants', 'yes') === 'yes') {
                continue;
            }
            
            // Check if variation already exists
            $variation_id = $this->findExistingVariation($product_id, $variant);
            
            if (!$variation_id) {
                // Create new variation
                $variation = new \WC_Product_Variation();
                $variation->set_parent_id($product_id);
            } else {
                // Update existing variation
                $variation = wc_get_product($variation_id);
                
                // Remove from existing variations array to track which ones we didn't update
                $existing_variations = array_diff($existing_variations, [$variation_id]);
            }
            
            // Set variation data
            $variation->set_regular_price($variant['price'] / 100);
            $variation->set_sku($variant['sku'] ?? '');
            
            // Set stock status
            if (isset($variant['is_enabled']) && !$variant['is_enabled']) {
                $variation->set_stock_status('outofstock');
            } else {
                $variation->set_stock_status('instock');
            }
            
            // Set variation attributes
            $variation_attributes = [];
            
            foreach ($variant['options'] as $option) {
                $taxonomy = wc_attribute_taxonomy_name($option['name']);
                $value = $option['value'];
                
                if (taxonomy_exists($taxonomy)) {
                    $term = get_term_by('name', $value, $taxonomy);
                    if ($term) {
                        $value = $term->slug;
                    }
                    $variation_attributes[$taxonomy] = $value;
                } else {
                    $variation_attributes["pa_" . sanitize_title($option['name'])] = sanitize_title($value);
                }
            }
            
            $variation->set_attributes($variation_attributes);
            
            // Save variation
            $variation_id = $variation->save();
            
            // Set Printify metadata
            update_post_meta($variation_id, '_printify_variant_id', $variant['id']);
            update_post_meta($variation_id, '_printify_cost_price', $variant['cost'] / 100);
            
            // Handle variant-specific images
            if (isset($variant['image']) && !empty($variant['image'])) {
                $image_url = $variant['image'];
                $attachment_id = $this->downloadAndAttachImage($image_url, $product_id);
                
                if ($attachment_id) {
                    update_post_meta($variation_id, '_thumbnail_id', $attachment_id);
                }
            }
        }
        
        // Optionally delete variations that weren't updated
        if (!empty($existing_variations) && get_option('wpwps_delete_unused_variations', 'yes') === 'yes') {
            foreach ($existing_variations as $variation_id) {
                wp_delete_post($variation_id, true);
            }
        }
    }
    
    /**
     * Download and attach image to product
     * 
     * @param string $image_url Image URL
     * @param int $product_id WooCommerce product ID
     * @return int|false Attachment ID or false on failure
     */
    private function downloadAndAttachImage(string $image_url, int $product_id) {
        // Check if image already exists by URL
        $existing_attachment = $this->getAttachmentByUrl($image_url);
        
        if ($existing_attachment) {
            return $existing_attachment;
        }
        
        // Include required files
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Add a query string parameter to help with caching
        $image_url = add_query_arg('timestamp', time(), $image_url);
        
        // Download and attach image
        $attachment_id = media_sideload_image($image_url, $product_id, null, 'id');
        
        if (is_wp_error($attachment_id)) {
            $this->logger->log("Error downloading image: " . $attachment_id->get_error_message(), 'error');
            return false;
        }
        
        // Store original URL in attachment meta
        update_post_meta($attachment_id, '_wpwps_original_url', $image_url);
        
        return $attachment_id;
    }
    
    /**
     * Get attachment by original URL
     * 
     * @param string $url Image URL
     * @return int|false Attachment ID or false if not found
     */
    private function getAttachmentByUrl(string $url) {
        global $wpdb;
        
        // Clean URL by removing query parameters
        $parsed_url = parse_url($url);
        $clean_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
        
        // Search for attachment by meta
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpwps_original_url' AND meta_value LIKE %s",
            '%' . $clean_url . '%'
        ));
        
        if ($attachment_id) {
            return (int) $attachment_id;
        }
        
        return false;
    }
    
    /**
     * Find existing variation by comparing attributes
     * 
     * @param int $product_id WooCommerce product ID
     * @param array $variant Printify variant data
     * @return int|false Variation ID or false if not found
     */
    private function findExistingVariation(int $product_id, array $variant): ?int {
        // Try to find by printify_variant_id first
        global $wpdb;
        
        $variation_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_printify_variant_id' AND meta_value = %s",
            $variant['id']
        ));
        
        if ($variation_id) {
            return (int) $variation_id;
        }
        
        // If not found, look for variations with matching SKU
        if (!empty($variant['sku'])) {
            $sku_product = wc_get_product_id_by_sku($variant['sku']);
            
            if ($sku_product) {
                $product = wc_get_product($sku_product);
                
                if ($product && $product->is_type('variation') && $product->get_parent_id() === $product_id) {
                    return $sku_product;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Update Printify external_id field with WooCommerce product ID
     * 
     * @param string $printify_id Printify product ID
     * @param int $product_id WooCommerce product ID
     * @return bool Success
     */
    private function updatePrintifyExternalId(string $printify_id, int $product_id): bool {
        try {
            $data = [
                'external_id' => (string) $product_id
            ];
            
            $this->api->updateProduct($printify_id, $data);
            return true;
        } catch (\Exception $e) {
            $this->logger->log("Error updating Printify external_id: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Get WooCommerce product ID by Printify ID
     * 
     * @param string $printify_id Printify product ID
     * @return int|false WooCommerce product ID or false if not found
     */
    private function getWooProductIdByPrintifyId(string $printify_id) {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_printify_product_id' AND meta_value = %s",
            $printify_id
        ));
        
        if ($product_id) {
            return (int) $product_id;
        }
        
        return false;
    }
    
    /**
     * Get attribute taxonomy ID by name
     * 
     * @param string $name Attribute name
     * @return int|false Attribute ID or false if not found
     */
    private function getAttributeTaxonomyId(string $name): ?int {
        global $wpdb;
        
        $attribute_id = $wpdb->get_var($wpdb->prepare(
            "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_label = %s",
            $name
        ));
        
        if ($attribute_id) {
            return (int) $attribute_id;
        }
        
        return null;
    }
    
    /**
     * Create attribute taxonomy
     * 
     * @param string $name Attribute name
     * @return int|false Attribute ID or false on failure
     */
    private function createAttributeTaxonomy(string $name): ?int {
        global $wpdb;
        
        // Create sanitized name
        $sanitized_name = wc_sanitize_taxonomy_name($name);
        
        // Check if attribute already exists
        $existing_id = $this->getAttributeTaxonomyId($name);
        
        if ($existing_id) {
            return $existing_id;
        }
        
        // Insert new attribute
        $wpdb->insert(
            $wpdb->prefix . 'woocommerce_attribute_taxonomies',
            [
                'attribute_label' => $name,
                'attribute_name' => $sanitized_name,
                'attribute_type' => 'select',
                'attribute_orderby' => 'menu_order',
                'attribute_public' => 0
            ],
            ['%s', '%s', '%s', '%s', '%d']
        );
        
        $attribute_id = $wpdb->insert_id;
        
        // Flush rewrite rules and clear transients
        flush_rewrite_rules();
        delete_transient('wc_attribute_taxonomies');
        
        return $attribute_id;
    }
}
