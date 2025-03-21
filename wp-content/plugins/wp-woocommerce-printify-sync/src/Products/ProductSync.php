<?php
/**
 * Product Sync.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Products
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Products;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Product Sync class.
 */
class ProductSync {
    /**
     * PrintifyAPI instance.
     *
     * @var PrintifyAPI
     */
    private $api;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param PrintifyAPI $api    PrintifyAPI instance.
     * @param Logger      $logger Logger instance.
     */
    public function __construct(PrintifyAPI $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
    }

    /**
     * Initialize product sync.
     *
     * @return void
     */
    public function init() {
        // Register AJAX handlers.
        add_action('wp_ajax_wpwps_import_products', [$this, 'importProducts']);
        add_action('wp_ajax_wpwps_sync_product', [$this, 'syncProduct']);
        add_action('wp_ajax_wpwps_get_products', [$this, 'getProducts']);
        
        // Register Action Scheduler handlers.
        add_action('wpwps_product_sync', [$this, 'scheduledProductSync']);
        add_action('wpwps_import_product', [$this, 'importProduct'], 10, 1);
        
        // Add meta boxes to product edit page.
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        
        // Add product filters to WP-admin product list.
        add_action('restrict_manage_posts', [$this, 'addProductFilters']);
        add_filter('parse_query', [$this, 'filterProducts']);
        
        // HPOS compatibility.
        add_action('before_woocommerce_init', [$this, 'declareHposCompatibility']);
    }

    /**
     * Declare HPOS compatibility.
     *
     * @return void
     */
    public function declareHposCompatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                WPWPS_PLUGIN_BASENAME,
                true
            );
        }
    }

    /**
     * Import products from Printify.
     *
     * @return void
     */
    public function importProducts() {
        // Check nonce.
        check_ajax_referer('wpwps_products_nonce', 'nonce');
        
        // Check user capability.
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        // Get products from Printify.
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        
        $response = $this->api->getProducts($limit, $page);
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message(),
            ]);
            return;
        }
        
        // Schedule product import using Action Scheduler.
        $products = $response['data'];
        $total = $response['total'];
        $imported = 0;
        
        foreach ($products as $product) {
            // Schedule product import.
            as_schedule_single_action(time(), 'wpwps_import_product', [$product['id']], 'wpwps_product_import');
            $imported++;
        }
        
        // Calculate progress.
        $progress = ($page * $limit) / $total * 100;
        
        wp_send_json_success([
            'message' => sprintf(
                __('Scheduled import of %d products (page %d of %d).', 'wp-woocommerce-printify-sync'),
                $imported,
                $page,
                ceil($total / $limit)
            ),
            'page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_products' => $total,
            'progress' => min(100, $progress),
            'complete' => $page >= ceil($total / $limit),
        ]);
    }

    /**
     * Import a single product from Printify.
     *
     * @param string $product_id Printify product ID.
     * @return void
     */
    public function importProduct($product_id) {
        // Get product details from Printify.
        $product = $this->api->getProduct($product_id);
        
        if (is_wp_error($product)) {
            $this->logger->error(
                sprintf('Failed to import product %s', $product_id),
                ['error' => $product->get_error_message()]
            );
            return;
        }
        
        // Check if product already exists.
        $existing_product_id = $this->getProductIdByPrintifyId($product_id);
        
        if ($existing_product_id) {
            // Update existing product.
            $this->updateProduct($existing_product_id, $product);
        } else {
            // Create new product.
            $this->createProduct($product);
        }
    }

    /**
     * Create a new product in WooCommerce.
     *
     * @param array $product_data Printify product data.
     * @return int|false Product ID or false on failure.
     */
    private function createProduct($product_data) {
        $this->logger->info(
            sprintf('Creating product %s', $product_data['id']),
            ['title' => $product_data['title']]
        );
        
        // Create product object.
        $product = new \WC_Product_Variable();
        
        // Set product data.
        $product->set_name($product_data['title']);
        $product->set_description($product_data['description']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_featured(false);
        
        // Set prices to zero initially.
        $product->set_regular_price(0);
        $product->set_price(0);
        
        // Set product meta data.
        $product->update_meta_data('_printify_product_id', $product_data['id']);
        $product->update_meta_data('_printify_provider_id', $product_data['print_provider_id']);
        $product->update_meta_data('_printify_last_synced', current_time('mysql'));
        
        // Save product to get ID.
        $product_id = $product->save();
        
        if (!$product_id) {
            $this->logger->error(
                sprintf('Failed to create product %s', $product_data['id']),
                ['error' => 'Product save failed']
            );
            return false;
        }
        
        // Set product categories.
        if (!empty($product_data['tags'])) {
            $this->setProductCategories($product_id, $product_data['tags']);
        }
        
        // Set product tags.
        if (!empty($product_data['tags'])) {
            $this->setProductTags($product_id, $product_data['tags']);
        }
        
        // Import product images.
        if (!empty($product_data['images'])) {
            $this->importProductImages($product_id, $product_data['images']);
        }
        
        // Create product variations.
        if (!empty($product_data['variants'])) {
            $this->createProductVariations($product_id, $product_data);
        }
        
        $this->logger->info(
            sprintf('Product %s created successfully', $product_data['id']),
            ['product_id' => $product_id]
        );
        
        return $product_id;
    }

    /**
     * Update an existing product in WooCommerce.
     *
     * @param int   $product_id   WooCommerce product ID.
     * @param array $product_data Printify product data.
     * @return bool Success or failure.
     */
    private function updateProduct($product_id, $product_data) {
        $this->logger->info(
            sprintf('Updating product %s', $product_data['id']),
            ['product_id' => $product_id, 'title' => $product_data['title']]
        );
        
        // Get product object.
        $product = wc_get_product($product_id);
        
        if (!$product) {
            $this->logger->error(
                sprintf('Failed to update product %s', $product_data['id']),
                ['error' => 'Product not found']
            );
            return false;
        }
        
        // Update product data.
        $product->set_name($product_data['title']);
        $product->set_description($product_data['description']);
        
        // Update product meta data.
        $product->update_meta_data('_printify_provider_id', $product_data['print_provider_id']);
        $product->update_meta_data('_printify_last_synced', current_time('mysql'));
        
        // Save product changes.
        $product->save();
        
        // Update product categories.
        if (!empty($product_data['tags'])) {
            $this->setProductCategories($product_id, $product_data['tags']);
        }
        
        // Update product tags.
        if (!empty($product_data['tags'])) {
            $this->setProductTags($product_id, $product_data['tags']);
        }
        
        // Update product images.
        if (!empty($product_data['images'])) {
            $this->importProductImages($product_id, $product_data['images']);
        }
        
        // Update product variations.
        if (!empty($product_data['variants'])) {
            $this->updateProductVariations($product_id, $product_data);
        }
        
        $this->logger->info(
            sprintf('Product %s updated successfully', $product_data['id']),
            ['product_id' => $product_id]
        );
        
        return true;
    }

    /**
     * Set product categories.
     *
     * @param int   $product_id WooCommerce product ID.
     * @param array $tags       Product tags.
     * @return void
     */
    private function setProductCategories($product_id, $tags) {
        // Create hierarchical categories from tags.
        $category_ids = [];
        
        foreach ($tags as $tag) {
            if (strpos($tag, '>') !== false) {
                // This is a hierarchical category.
                $hierarchy = array_map('trim', explode('>', $tag));
                $parent_id = 0;
                
                foreach ($hierarchy as $category_name) {
                    $category = get_term_by('name', $category_name, 'product_cat');
                    
                    if (!$category) {
                        // Create new category.
                        $result = wp_insert_term(
                            $category_name,
                            'product_cat',
                            [
                                'parent' => $parent_id,
                            ]
                        );
                        
                        if (!is_wp_error($result)) {
                            $parent_id = $result['term_id'];
                            
                            if (end($hierarchy) === $category_name) {
                                $category_ids[] = $result['term_id'];
                            }
                        }
                    } else {
                        $parent_id = $category->term_id;
                        
                        if (end($hierarchy) === $category_name) {
                            $category_ids[] = $category->term_id;
                        }
                    }
                }
            } else {
                // This is a regular category.
                $category = get_term_by('name', $tag, 'product_cat');
                
                if (!$category) {
                    // Create new category.
                    $result = wp_insert_term($tag, 'product_cat');
                    
                    if (!is_wp_error($result)) {
                        $category_ids[] = $result['term_id'];
                    }
                } else {
                    $category_ids[] = $category->term_id;
                }
            }
        }
        
        // Set product categories.
        if (!empty($category_ids)) {
            wp_set_object_terms($product_id, $category_ids, 'product_cat');
        }
    }

    /**
     * Set product tags.
     *
     * @param int   $product_id WooCommerce product ID.
     * @param array $tags       Product tags.
     * @return void
     */
    private function setProductTags($product_id, $tags) {
        wp_set_object_terms($product_id, $tags, 'product_tag');
    }

    /**
     * Import product images.
     *
     * @param int   $product_id WooCommerce product ID.
     * @param array $images     Product images.
     * @return void
     */
    private function importProductImages($product_id, $images) {
        if (empty($images)) {
            return;
        }
        
        // Get product object.
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return;
        }
        
        // Import main image.
        $main_image_id = $this->importImage($images[0]['src']);
        
        if ($main_image_id) {
            $product->set_image_id($main_image_id);
        }
        
        // Import gallery images.
        $gallery_image_ids = [];
        
        for ($i = 1; $i < count($images); $i++) {
            $image_id = $this->importImage($images[$i]['src']);
            
            if ($image_id) {
                $gallery_image_ids[] = $image_id;
            }
        }
        
        if (!empty($gallery_image_ids)) {
            $product->set_gallery_image_ids($gallery_image_ids);
        }
        
        // Save product.
        $product->save();
    }

    /**
     * Import an image and attach it to the media library.
     *
     * @param string $image_url Image URL.
     * @return int|false Attachment ID or false on failure.
     */
    private function importImage($image_url) {
        // Check if image already exists.
        $existing_attachment_id = $this->getAttachmentIdByUrl($image_url);
        
        if ($existing_attachment_id) {
            return $existing_attachment_id;
        }
        
        // Download and import image.
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            $this->logger->error(
                'Failed to download image',
                ['error' => $tmp->get_error_message(), 'url' => $image_url]
            );
            return false;
        }
        
        $file_array = [
            'name' => basename($image_url),
            'tmp_name' => $tmp,
        ];
        
        $attachment_id = media_handle_sideload($file_array, 0);
        
        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            $this->logger->error(
                'Failed to import image',
                ['error' => $attachment_id->get_error_message(), 'url' => $image_url]
            );
            return false;
        }
        
        // Store the original URL as post meta.
        update_post_meta($attachment_id, '_printify_original_url', $image_url);
        
        return $attachment_id;
    }

    /**
     * Get attachment ID by URL.
     *
     * @param string $url Image URL.
     * @return int|false Attachment ID or false if not found.
     */
    private function getAttachmentIdByUrl($url) {
        global $wpdb;
        
        $attachment = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_printify_original_url' AND meta_value = %s",
                $url
            )
        );
        
        return isset($attachment[0]) ? $attachment[0] : false;
    }

    /**
     * Create product variations.
     *
     * @param int   $product_id   WooCommerce product ID.
     * @param array $product_data Printify product data.
     * @return void
     */
    private function createProductVariations($product_id, $product_data) {
        // Get product object.
        $product = wc_get_product($product_id);
        
        if (!$product || !$product instanceof \WC_Product_Variable) {
            return;
        }
        
        // Extract attributes from variants.
        $attributes = $this->extractAttributesFromVariants($product_data['variants']);
        
        // Set product attributes.
        $this->setProductAttributes($product_id, $attributes);
        
        // Create variations.
        foreach ($product_data['variants'] as $variant) {
            $this->createVariation($product_id, $variant, $attributes);
        }
        
        // Update product meta.
        $printify_variant_ids = array_column($product_data['variants'], 'id');
        $product->update_meta_data('_printify_variant_ids', $printify_variant_ids);
        $product->save();
    }

    /**
     * Update product variations.
     *
     * @param int   $product_id   WooCommerce product ID.
     * @param array $product_data Printify product data.
     * @return void
     */
    private function updateProductVariations($product_id, $product_data) {
        // Get product object.
        $product = wc_get_product($product_id);
        
        if (!$product || !$product instanceof \WC_Product_Variable) {
            return;
        }
        
        // Extract attributes from variants.
        $attributes = $this->extractAttributesFromVariants($product_data['variants']);
        
        // Set product attributes.
        $this->setProductAttributes($product_id, $attributes);
        
        // Get existing variations.
        $existing_variations = $product->get_children();
        $existing_variation_data = [];
        
        foreach ($existing_variations as $variation_id) {
            $variation = wc_get_product($variation_id);
            $printify_variant_id = $variation->get_meta('_printify_variant_id');
            
            if ($printify_variant_id) {
                $existing_variation_data[$printify_variant_id] = $variation_id;
            }
        }
        
        // Update or create variations.
        foreach ($product_data['variants'] as $variant) {
            if (isset($existing_variation_data[$variant['id']])) {
                // Update existing variation.
                $this->updateVariation($existing_variation_data[$variant['id']], $variant, $attributes);
            } else {
                // Create new variation.
                $this->createVariation($product_id, $variant, $attributes);
            }
        }
        
        // Delete variations that no longer exist.
        $printify_variant_ids = array_column($product_data['variants'], 'id');
        
        foreach ($existing_variation_data as $printify_variant_id => $variation_id) {
            if (!in_array($printify_variant_id, $printify_variant_ids)) {
                wp_delete_post($variation_id, true);
            }
        }
        
        // Update product meta.
        $product->update_meta_data('_printify_variant_ids', $printify_variant_ids);
        $product->save();
    }

    /**
     * Extract attributes from variants.
     *
     * @param array $variants Product variants.
     * @return array Attributes.
     */
    private function extractAttributesFromVariants($variants) {
        $attributes = [];
        
        foreach ($variants as $variant) {
            foreach ($variant['options'] as $option_name => $option_value) {
                if (!isset($attributes[$option_name])) {
                    $attributes[$option_name] = [];
                }
                
                if (!in_array($option_value, $attributes[$option_name])) {
                    $attributes[$option_name][] = $option_value;
                }
            }
        }
        
        return $attributes;
    }

    /**
     * Set product attributes.
     *
     * @param int   $product_id WooCommerce product ID.
     * @param array $attributes Attributes.
     * @return void
     */
    private function setProductAttributes($product_id, $attributes) {
        // Get product object.
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return;
        }
        
        // Prepare attributes.
        $product_attributes = [];
        
        foreach ($attributes as $name => $values) {
            $attribute_id = wc_attribute_taxonomy_id_by_name($name);
            
            if (!$attribute_id) {
                // Create attribute.
                wc_create_attribute([
                    'name' => $name,
                    'slug' => sanitize_title($name),
                    'type' => 'select',
                    'order_by' => 'menu_order',
                    'has_archives' => false,
                ]);
                
                // Register the taxonomy.
                $attribute_id = wc_attribute_taxonomy_id_by_name($name);
            }
            
            $taxonomy = wc_attribute_taxonomy_name($name);
            
            // Register the taxonomy if it doesn't exist.
            if (!taxonomy_exists($taxonomy)) {
                register_taxonomy(
                    $taxonomy,
                    apply_filters('woocommerce_taxonomy_objects_' . $taxonomy, ['product']),
                    apply_filters('woocommerce_taxonomy_args_' . $taxonomy, [
                        'hierarchical' => true,
                        'show_ui' => false,
                        'query_var' => true,
                        'rewrite' => false,
                    ])
                );
            }
            
            // Add attribute values.
            foreach ($values as $value) {
                $term = get_term_by('name', $value, $taxonomy);
                
                if (!$term) {
                    $term = wp_insert_term($value, $taxonomy);
                    $term = get_term_by('id', $term['term_id'], $taxonomy);
                }
            }
            
            // Add attribute to product.
            $product_attributes[$taxonomy] = [
                'name' => $taxonomy,
                'value' => '',
                'position' => array_search($name, array_keys($attributes)),
                'is_visible' => 1,
                'is_variation' => 1,
                'is_taxonomy' => 1,
            ];
        }
        
        // Set product attributes.
        $product->set_attributes($product_attributes);
        $product->save();
    }

    /**
     * Create product variation.
     *
     * @param int   $product_id WooCommerce product ID.
     * @param array $variant    Variant data.
     * @param array $attributes Product attributes.
     * @return int|false Variation ID or false on failure.
     */
    private function createVariation($product_id, $variant, $attributes) {
        // Create variation object.
        $variation = new \WC_Product_Variation();
        $variation->set_parent_id($product_id);
        
        // Set variation data.
        $variation->set_regular_price($variant['price']);
        $variation->update_meta_data('_printify_cost_price', $variant['cost']);
        $variation->update_meta_data('_printify_variant_id', $variant['id']);
        $variation->set_sku($variant['sku']);
        
        // Set variation attributes.
        $variation_attributes = [];
        
        foreach ($variant['options'] as $option_name => $option_value) {
            $taxonomy = wc_attribute_taxonomy_name($option_name);
            $variation_attributes[$taxonomy] = $option_value;
        }
        
        $variation->set_attributes($variation_attributes);
        
        // Set stock status.
        if ($variant['is_enabled'] && !$variant['is_default']) {
            $variation->set_status('publish');
            $variation->set_stock_status('instock');
            $variation->set_manage_stock(false);
        } else {
            $variation->set_status('private');
            $variation->set_stock_status('outofstock');
            $variation->set_manage_stock(false);
        }
        
        // Save variation.
        return $variation->save();
    }

    /**
     * Update product variation.
     *
     * @param int   $variation_id Variation ID.
     * @param array $variant      Variant data.
     * @param array $attributes   Product attributes.
     * @return int|false Variation ID or false on failure.
     */
    private function updateVariation($variation_id, $variant, $attributes) {
        // Get variation object.
        $variation = wc_get_product($variation_id);
        
        if (!$variation) {
            return false;
        }
        
        // Update variation data.
        $variation->set_regular_price($variant['price']);
        $variation->update_meta_data('_printify_cost_price', $variant['cost']);
        $variation->set_sku($variant['sku']);
        
        // Update variation attributes.
        $variation_attributes = [];
        
        foreach ($variant['options'] as $option_name => $option_value) {
            $taxonomy = wc_attribute_taxonomy_name($option_name);
            $variation_attributes[$taxonomy] = $option_value;
        }
        
        $variation->set_attributes($variation_attributes);
        
        // Update stock status.
        if ($variant['is_enabled'] && !$variant['is_default']) {
            $variation->set_status('publish');
            $variation->set_stock_status('instock');
            $variation->set_manage_stock(false);
        } else {
            $variation->set_status('private');
            $variation->set_stock_status('outofstock');
            $variation->set_manage_stock(false);
        }
        
        // Save variation.
        return $variation->save();
    }

    /**
     * Get products from Printify.
     *
     * @return void
     */
    public function getProducts() {
        // Check nonce.
        check_ajax_referer('wpwps_products_nonce', 'nonce');
        
        // Check user capability.
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        // Get products from Printify.
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        
        $response = $this->api->getProducts($limit, $page);
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message(),
            ]);
            return;
        }
        
        // Format products for display.
        $products = [];
        
        foreach ($response['data'] as $product) {
            $wc_product_id = $this->getProductIdByPrintifyId($product['id']);
            
            $products[] = [
                'id' => $product['id'],
                'title' => $product['title'],
                'image' => isset($product['images'][0]) ? $product['images'][0]['src'] : '',
                'variants' => count($product['variants']),
                'published' => $product['published'],
                'wc_product_id' => $wc_product_id,
                'wc_product_url' => $wc_product_id ? get_edit_post_link($wc_product_id) : '',
                'synced' => (bool) $wc_product_id,
            ];
        }
        
        wp_send_json_success([
            'products' => $products,
            'page' => $page,
            'total_pages' => ceil($response['total'] / $limit),
            'total_products' => $response['total'],
        ]);
    }

    /**
     * Sync a single product.
     *
     * @return void
     */
    public function syncProduct() {
        // Check nonce.
        check_ajax_referer('wpwps_products_nonce', 'nonce');
        
        // Check user capability.
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            ]);
        }
        
        // Get product ID.
        $product_id = isset($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : '';
        
        if (empty($product_id)) {
            wp_send_json_error([
                'message' => __('Product ID is required.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }
        
        // Schedule product import.
        as_schedule_single_action(time(), 'wpwps_import_product', [$product_id], 'wpwps_product_import');
        
        wp_send_json_success([
            'message' => __('Product sync scheduled.', 'wp-woocommerce-printify-sync'),
        ]);
    }

    /**
     * Scheduled product sync.
     *
     * @return void
     */
    public function scheduledProductSync() {
        $this->logger->info('Starting scheduled product sync.');
        
        // Get all Printify products.
        $page = 1;
        $limit = 50;
        $total_pages = 1;
        
        while ($page <= $total_pages) {
            $response = $this->api->getProducts($limit, $page);
            
            if (is_wp_error($response)) {
                $this->logger->error(
                    'Failed to get products from Printify.',
                    ['error' => $response->get_error_message()]
                );
                return;
            }
            
            $products = $response['data'];
            $total_pages = ceil($response['total'] / $limit);
            
            foreach ($products as $product) {
                // Schedule product import.
                as_schedule_single_action(time(), 'wpwps_import_product', [$product['id']], 'wpwps_product_import');
            }
            
            $page++;
        }
        
        $this->logger->info('Scheduled product sync completed.');
    }

    /**
     * Get WooCommerce product ID by Printify product ID.
     *
     * @param string $printify_id Printify product ID.
     * @return int|false Product ID or false if not found.
     */
    private function getProductIdByPrintifyId($printify_id) {
        global $wpdb;
        
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_printify_product_id' AND meta_value = %s LIMIT 1",
                $printify_id
            )
        );
        
        return $product_id ? intval($product_id) : false;
    }

    /**
     * Add meta boxes to product edit page.
     *
     * @return void
     */
    public function addMetaBoxes() {
        add_meta_box(
            'wpwps_product_info',
            __('Printify Product', 'wp-woocommerce-printify-sync'),
            [$this, 'renderProductInfoMetaBox'],
            'product',
            'side',
            'high'
        );
    }

    /**
     * Render product info meta box.
     *
     * @param \WP_Post $post Post object.
     * @return void
     */
    public function renderProductInfoMetaBox($post) {
        $product_id = $post->ID;
        $printify_id = get_post_meta($product_id, '_printify_product_id', true);
        
        if (!$printify_id) {
            echo '<p>' . esc_html__('This product is not linked to Printify.', 'wp-woocommerce-printify-sync') . '</p>';
            return;
        }
        
        $provider_id = get_post_meta($product_id, '_printify_provider_id', true);
        $last_synced = get_post_meta($product_id, '_printify_last_synced', true);
        
        echo '<p><strong>' . esc_html__('Printify ID:', 'wp-woocommerce-printify-sync') . '</strong> ' . esc_html($printify_id) . '</p>';
        echo '<p><strong>' . esc_html__('Provider ID:', 'wp-woocommerce-printify-sync') . '</strong> ' . esc_html($provider_id) . '</p>';
        
        if ($last_synced) {
            echo '<p><strong>' . esc_html__('Last Synced:', 'wp-woocommerce-printify-sync') . '</strong> ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_synced))) . '</p>';
        }
        
        echo '<p><a href="#" class="button sync-printify-product" data-product-id="' . esc_attr($printify_id) . '" data-nonce="' . esc_attr(wp_create_nonce('wpwps_products_nonce')) . '">' . esc_html__('Sync Now', 'wp-woocommerce-printify-sync') . '</a></p>';
        
        // Add JavaScript for sync button.
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.sync-printify-product').on('click', function(e) {
                    e.preventDefault();
                    
                    const button = $(this);
                    const productId = button.data('product-id');
                    const nonce = button.data('nonce');
                    
                    // Disable button and show loading text.
                    button.prop('disabled', true).text('<?php esc_html_e('Syncing...', 'wp-woocommerce-printify-sync'); ?>');
                    
                    // Send AJAX request.
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wpwps_sync_product',
                            product_id: productId,
                            nonce: nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                button.text('<?php esc_html_e('Sync Scheduled', 'wp-woocommerce-printify-sync'); ?>');
                                setTimeout(function() {
                                    button.prop('disabled', false).text('<?php esc_html_e('Sync Now', 'wp-woocommerce-printify-sync'); ?>');
                                }, 3000);
                            } else {
                                button.text('<?php esc_html_e('Error', 'wp-woocommerce-printify-sync'); ?>');
                                alert(response.data.message);
                                setTimeout(function() {
                                    button.prop('disabled', false).text('<?php esc_html_e('Sync Now', 'wp-woocommerce-printify-sync'); ?>');
                                }, 3000);
                            }
                        },
                        error: function() {
                            button.text('<?php esc_html_e('Error', 'wp-woocommerce-printify-sync'); ?>');
                            alert('<?php esc_html_e('An error occurred. Please try again.', 'wp-woocommerce-printify-sync'); ?>');
                            setTimeout(function() {
                                button.prop('disabled', false).text('<?php esc_html_e('Sync Now', 'wp-woocommerce-printify-sync'); ?>');
                            }, 3000);
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Add product filters to WP-admin product list.
     *
     * @param string $post_type Post type.
     * @return void
     */
    public function addProductFilters($post_type) {
        if ('product' !== $post_type) {
            return;
        }
        
        // Add Printify filter.
        ?>
        <select name="printify_filter">
            <option value=""><?php esc_html_e('All products', 'wp-woocommerce-printify-sync'); ?></option>
            <option value="printify" <?php selected(isset($_GET['printify_filter']) && 'printify' === $_GET['printify_filter']); ?>><?php esc_html_e('Printify products', 'wp-woocommerce-printify-sync'); ?></option>
            <option value="not_printify" <?php selected(isset($_GET['printify_filter']) && 'not_printify' === $_GET['printify_filter']); ?>><?php esc_html_e('Non-Printify products', 'wp-woocommerce-printify-sync'); ?></option>
        </select>
        <?php
    }

    /**
     * Filter products in WP-admin product list.
     *
     * @param \WP_Query $query Query object.
     * @return \WP_Query
     */
    public function filterProducts($query) {
        global $pagenow, $typenow;
        
        if ('edit.php' !== $pagenow || 'product' !== $typenow || !is_admin()) {
            return $query;
        }
        
        if (!isset($_GET['printify_filter']) || empty($_GET['printify_filter'])) {
            return $query;
        }
        
        $filter = sanitize_text_field($_GET['printify_filter']);
        
        if ('printify' === $filter) {
            // Show only Printify products.
            $query->query_vars['meta_query'][] = [
                'key' => '_printify_product_id',
                'compare' => 'EXISTS',
            ];
        } elseif ('not_printify' === $filter) {
            // Show only non-Printify products.
            $query->query_vars['meta_query'][] = [
                'key' => '_printify_product_id',
                'compare' => 'NOT EXISTS',
            ];
        }
        
        return $query;
    }
}
