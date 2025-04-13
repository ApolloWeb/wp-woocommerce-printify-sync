<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Products;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApi;
use ApolloWeb\WPWooCommercePrintifySync\Logger\SyncLogger;

/**
 * Product Sync Service
 * 
 * Handles product imports and updates from Printify to WooCommerce
 */
class ProductSyncService {
    /**
     * @var PrintifyApi
     */
    private $api;
    
    /**
     * @var SyncLogger
     */
    private $logger;
    
    /**
     * @var string
     */
    private $shop_id;
    
    /**
     * Constructor
     */
    public function __construct() {
        $settings = new \ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsService();
        $printify_settings = $settings->getPrintifySettings();
        
        $this->api = new PrintifyApi(
            $printify_settings['api_key'],
            $printify_settings['api_endpoint']
        );
        
        $this->shop_id = $printify_settings['shop_id'];
        $this->logger = new SyncLogger();
    }
    
    /**
     * Start a full product sync
     *
     * @param bool $force Force sync even if products exist
     * @return void
     */
    public function start_full_sync($force = false) {
        // Check if we should proceed
        if (!$this->should_sync($force)) {
            return;
        }
        
        try {
            // Get products from Printify
            $response = $this->api->get_products($this->shop_id);
            
            if (is_wp_error($response)) {
                $this->logger->log_error('product_sync_start', $response->get_error_message());
                return;
            }
            
            $total_products = count($response);
            
            $this->logger->log_info(
                'product_sync_start',
                sprintf(__('Starting sync of %d products from Printify', 'wp-woocommerce-printify-sync'), $total_products)
            );
            
            // Queue products for processing
            foreach ($response as $product) {
                $this->schedule_product_import($product['id']);
            }
            
            // Schedule completion check
            as_schedule_single_action(
                time() + 300, // 5 minutes later
                'wpwps_check_sync_completion',
                ['total_products' => $total_products],
                'wpwps'
            );
            
        } catch (\Exception $e) {
            $this->logger->log_error('product_sync_start', $e->getMessage());
        }
    }
    
    /**
     * Check if we should sync products
     *
     * @param bool $force Force sync
     * @return bool
     */
    private function should_sync($force) {
        if ($force) {
            return true;
        }
        
        // Check if API key and shop ID are set
        if (empty($this->shop_id)) {
            $this->logger->log_error('product_sync_check', __('Missing Printify Shop ID', 'wp-woocommerce-printify-sync'));
            return false;
        }
        
        // Check if sync is already running
        $is_syncing = get_option('wpwps_sync_in_progress', false);
        if ($is_syncing) {
            $this->logger->log_info('product_sync_check', __('Sync is already in progress', 'wp-woocommerce-printify-sync'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Schedule product import as an async action
     *
     * @param string $product_id Printify Product ID
     * @return void
     */
    public function schedule_product_import($product_id) {
        // Update sync status
        update_option('wpwps_sync_in_progress', true);
        
        // Schedule product import
        as_schedule_single_action(
            time(),
            'wpwps_import_product',
            ['printify_product_id' => $product_id],
            'wpwps'
        );
        
        $this->logger->log_info(
            'product_schedule',
            sprintf(__('Scheduled import for product ID: %s', 'wp-woocommerce-printify-sync'), $product_id)
        );
    }
    
    /**
     * Import a single product from Printify
     *
     * @param string $printify_product_id Printify Product ID
     * @return int|WP_Error WooCommerce product ID or error
     */
    public function import_product($printify_product_id) {
        try {
            // Get product data from Printify
            $product_data = $this->api->get_product($this->shop_id, $printify_product_id);
            
            if (is_wp_error($product_data)) {
                $this->logger->log_error('product_import', $product_data->get_error_message(), [
                    'printify_id' => $printify_product_id
                ]);
                return $product_data;
            }
            
            // Check if product already exists in WooCommerce
            $wc_product_id = $this->get_woocommerce_product_id($printify_product_id);
            
            if ($wc_product_id) {
                // Update existing product
                $result = $this->update_woocommerce_product($wc_product_id, $product_data);
            } else {
                // Create new product
                $result = $this->create_woocommerce_product($product_data);
            }
            
            if (is_wp_error($result)) {
                $this->logger->log_error('product_import', $result->get_error_message(), [
                    'printify_id' => $printify_product_id
                ]);
                return $result;
            }
            
            // Schedule image imports
            $this->schedule_image_imports($result, $product_data);
            
            // Register external product ID with Printify
            $this->register_external_product($printify_product_id, $result);
            
            $this->logger->log_success('product_import', __('Product imported/updated successfully', 'wp-woocommerce-printify-sync'), [
                'printify_id' => $printify_product_id,
                'product_id' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->log_error('product_import', $e->getMessage(), [
                'printify_id' => $printify_product_id
            ]);
            return new \WP_Error('import_error', $e->getMessage());
        }
    }
    
    /**
     * Get WooCommerce product ID by Printify ID
     *
     * @param string $printify_id Printify Product ID
     * @return int|false WooCommerce product ID or false
     */
    private function get_woocommerce_product_id($printify_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
            '_printify_product_id',
            $printify_id
        );
        
        $result = $wpdb->get_var($query);
        
        return $result ? (int)$result : false;
    }
    
    /**
     * Create a new WooCommerce variable product
     *
     * @param array $product_data Printify product data
     * @return int|WP_Error WooCommerce product ID or error
     */
    private function create_woocommerce_product($product_data) {
        // Create the product
        $product = new \WC_Product_Variable();
        
        // Set product data
        $product->set_name($product_data['title']);
        $product->set_description($product_data['description']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_featured(false);
        
        // Set SKU if available
        if (!empty($product_data['sku'])) {
            $product->set_sku($product_data['sku']);
        }
        
        // Set product categories
        if (!empty($product_data['product_type'])) {
            $this->set_product_categories($product, $product_data['product_type']);
        }
        
        // Set product tags
        if (!empty($product_data['tags'])) {
            $product->set_tag_ids($this->get_or_create_tags($product_data['tags']));
        }
        
        // Save the product to get an ID
        $product_id = $product->save();
        
        if (!$product_id) {
            return new \WP_Error('product_create_failed', __('Failed to create WooCommerce product', 'wp-woocommerce-printify-sync'));
        }
        
        // Set Printify metadata
        $this->set_printify_metadata($product_id, $product_data);
        
        // Create variations
        $this->create_product_variations($product_id, $product_data);
        
        return $product_id;
    }
    
    /**
     * Update an existing WooCommerce product
     *
     * @param int   $product_id   WooCommerce product ID
     * @param array $product_data Printify product data
     * @return int|WP_Error WooCommerce product ID or error
     */
    private function update_woocommerce_product($product_id, $product_data) {
        // Get the product
        $product = wc_get_product($product_id);
        
        if (!$product || !($product instanceof \WC_Product_Variable)) {
            return new \WP_Error('invalid_product', __('Invalid WooCommerce product', 'wp-woocommerce-printify-sync'));
        }
        
        // Update product data
        $product->set_name($product_data['title']);
        $product->set_description($product_data['description']);
        
        // Set SKU if available
        if (!empty($product_data['sku'])) {
            $product->set_sku($product_data['sku']);
        }
        
        // Set product categories
        if (!empty($product_data['product_type'])) {
            $this->set_product_categories($product, $product_data['product_type']);
        }
        
        // Set product tags
        if (!empty($product_data['tags'])) {
            $product->set_tag_ids($this->get_or_create_tags($product_data['tags']));
        }
        
        // Save the product
        $product_id = $product->save();
        
        // Update Printify metadata
        $this->set_printify_metadata($product_id, $product_data);
        
        // Update variations
        $this->update_product_variations($product_id, $product_data);
        
        return $product_id;
    }
    
    /**
     * Set Printify metadata for a product
     *
     * @param int   $product_id   WooCommerce product ID
     * @param array $product_data Printify product data
     */
    private function set_printify_metadata($product_id, $product_data) {
        // Set Printify metadata
        update_post_meta($product_id, '_printify_product_id', $product_data['id']);
        update_post_meta($product_id, '_printify_blueprint_id', $product_data['blueprint_id']);
        update_post_meta($product_id, '_printify_shop_id', $this->shop_id);
        
        // Add print provider info
        if (!empty($product_data['print_provider'])) {
            update_post_meta($product_id, '_printify_provider_id', $product_data['print_provider']['id']);
            update_post_meta($product_id, '_printify_print_provider_name', $product_data['print_provider']['title']);
        }
        
        update_post_meta($product_id, '_printify_last_synced', current_time('mysql'));
        update_post_meta($product_id, '_printify_is_synced', true);
    }
    
    /**
     * Create product variations from Printify variants
     *
     * @param int   $product_id   WooCommerce product ID
     * @param array $product_data Printify product data
     */
    private function create_product_variations($product_id, $product_data) {
        // Get the product
        $product = wc_get_product($product_id);
        
        if (!$product || !($product instanceof \WC_Product_Variable)) {
            return;
        }
        
        // Process variants
        if (!empty($product_data['variants'])) {
            // Extract attributes from variants
            $attributes = $this->extract_attributes_from_variants($product_data['variants']);
            
            // Set product attributes
            $this->set_product_attributes($product_id, $attributes);
            
            // Create variations
            foreach ($product_data['variants'] as $variant) {
                $this->create_variation($product_id, $variant, $attributes);
            }
            
            // Set default attributes
            $this->set_default_attributes($product_id, $product_data['variants'], $attributes);
        }
        
        // Update parent product's price from variations
        $this->update_parent_product_price($product_id);
    }
    
    /**
     * Update product variations
     *
     * @param int   $product_id   WooCommerce product ID
     * @param array $product_data Printify product data
     */
    private function update_product_variations($product_id, $product_data) {
        // First, remove existing variations to avoid duplicates
        $this->delete_existing_variations($product_id);
        
        // Then recreate variations
        $this->create_product_variations($product_id, $product_data);
    }
    
    /**
     * Extract attributes from variants
     *
     * @param array $variants Printify variants
     * @return array Attributes array
     */
    private function extract_attributes_from_variants($variants) {
        $attributes = [];
        
        foreach ($variants as $variant) {
            if (!empty($variant['options'])) {
                foreach ($variant['options'] as $option_name => $option_value) {
                    $attribute_name = wc_clean($option_name);
                    $attribute_value = wc_clean($option_value);
                    
                    if (!isset($attributes[$attribute_name])) {
                        $attributes[$attribute_name] = [];
                    }
                    
                    if (!in_array($attribute_value, $attributes[$attribute_name])) {
                        $attributes[$attribute_name][] = $attribute_value;
                    }
                }
            }
        }
        
        return $attributes;
    }
    
    /**
     * Set product attributes
     *
     * @param int   $product_id WooCommerce product ID
     * @param array $attributes Attributes array
     */
    private function set_product_attributes($product_id, $attributes) {
        $product_attributes = [];
        
        foreach ($attributes as $name => $values) {
            $attribute_id = wc_attribute_taxonomy_id_by_name($name);
            
            if (!$attribute_id) {
                $attribute_id = $this->create_attribute_taxonomy($name);
            }
            
            if ($attribute_id) {
                $attribute_name = wc_attribute_taxonomy_name_by_id($attribute_id);
            } else {
                $attribute_name = sanitize_title($name);
            }
            
            // Create attribute terms
            $term_ids = [];
            foreach ($values as $value) {
                if ($attribute_id) {
                    // Global attribute
                    $term = term_exists($value, $attribute_name);
                    
                    if (!$term) {
                        $term = wp_insert_term($value, $attribute_name);
                    }
                    
                    if (!is_wp_error($term)) {
                        $term_ids[] = $term['term_id'];
                    }
                }
            }
            
            // Store attribute data
            $product_attributes[$attribute_name] = [
                'name' => $attribute_name,
                'value' => '',
                'position' => 0,
                'is_visible' => 1,
                'is_variation' => 1,
                'is_taxonomy' => 1,
                'terms' => $term_ids
            ];
            
            // Set attribute terms if global attribute
            if ($attribute_id) {
                wp_set_object_terms($product_id, $term_ids, $attribute_name);
            }
        }
        
        // Save attributes to product
        update_post_meta($product_id, '_product_attributes', $product_attributes);
    }
    
    /**
     * Create a product variation
     *
     * @param int   $product_id WooCommerce product ID
     * @param array $variant    Printify variant
     * @param array $attributes Product attributes
     */
    private function create_variation($product_id, $variant, $attributes) {
        // Create the variation
        $variation = new \WC_Product_Variation();
        $variation->set_parent_id($product_id);
        
        // Set variation attributes
        if (!empty($variant['options'])) {
            $variation_attributes = [];
            
            foreach ($variant['options'] as $name => $value) {
                $attribute_name = 'attribute_' . sanitize_title(wc_clean($name));
                $variation_attributes[$attribute_name] = wc_clean($value);
            }
            
            $variation->set_attributes($variation_attributes);
        }
        
        // Set variation data
        $variation->set_status('publish');
        
        if (!empty($variant['sku'])) {
            $variation->set_sku($variant['sku']);
        }
        
        if (!empty($variant['price'])) {
            $variation->set_regular_price($variant['price']);
        }
        
        if (!empty($variant['cost'])) {
            $variation->update_meta_data('_printify_cost_price', $variant['cost']);
        }
        
        // Set Printify metadata
        $variation->update_meta_data('_printify_variant_id', $variant['id']);
        $variation->update_meta_data('_printify_is_synced', true);
        
        // Set variation image later
        
        // Save the variation
        $variation_id = $variation->save();
        
        return $variation_id;
    }
    
    /**
     * Delete existing product variations
     *
     * @param int $product_id WooCommerce product ID
     */
    private function delete_existing_variations($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product || !($product instanceof \WC_Product_Variable)) {
            return;
        }
        
        $variation_ids = $product->get_children();
        
        foreach ($variation_ids as $variation_id) {
            wp_delete_post($variation_id, true);
        }
    }
    
    /**
     * Set default attributes for the product
     *
     * @param int   $product_id WooCommerce product ID
     * @param array $variants   Printify variants
     * @param array $attributes Product attributes
     */
    private function set_default_attributes($product_id, $variants, $attributes) {
        if (empty($variants) || empty($attributes)) {
            return;
        }
        
        // Get first available variant
        $default_variant = $variants[0];
        $default_attributes = [];
        
        foreach ($default_variant['options'] as $name => $value) {
            $attribute_name = sanitize_title(wc_clean($name));
            $default_attributes[$attribute_name] = wc_clean($value);
        }
        
        if (!empty($default_attributes)) {
            update_post_meta($product_id, '_default_attributes', $default_attributes);
        }
    }
    
    /**
     * Update parent product's price from variations
     *
     * @param int $product_id WooCommerce product ID
     */
    private function update_parent_product_price($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product || !($product instanceof \WC_Product_Variable)) {
            return;
        }
        
        $product->sync_price();
    }
    
    /**
     * Create attribute taxonomy if it doesn't exist
     *
     * @param string $name Attribute name
     * @return int|false Attribute ID or false on failure
     */
    private function create_attribute_taxonomy($name) {
        global $wpdb;
        
        $attribute_name = wc_sanitize_taxonomy_name($name);
        $attribute_label = $name;
        
        // Check if attribute exists
        $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name);
        
        if ($attribute_id) {
            return $attribute_id;
        }
        
        // Create attribute
        $args = [
            'name' => $attribute_label,
            'slug' => $attribute_name,
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => 0,
        ];
        
        $result = wc_create_attribute($args);
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // Register the taxonomy
        register_taxonomy(
            'pa_' . $attribute_name,
            ['product'],
            [
                'labels' => [
                    'name' => $attribute_label,
                ],
                'hierarchical' => false,
                'show_ui' => true,
                'query_var' => true,
                'rewrite' => [
                    'slug' => 'pa_' . $attribute_name,
                ],
            ]
        );
        
        // Flush rewrite rules
        delete_transient('wc_attribute_taxonomies');
        
        return $result;
    }
    
    /**
     * Set product categories based on Printify product type
     *
     * @param WC_Product $product     WooCommerce product
     * @param string     $product_type Printify product type
     */
    private function set_product_categories($product, $product_type) {
        $category_ids = [];
        
        // Get or create category based on product type
        $term = term_exists($product_type, 'product_cat');
        
        if (!$term) {
            $term = wp_insert_term($product_type, 'product_cat');
        }
        
        if (!is_wp_error($term)) {
            $category_ids[] = $term['term_id'];
        }
        
        if (!empty($category_ids)) {
            $product->set_category_ids($category_ids);
        }
    }
    
    /**
     * Get or create product tags
     *
     * @param array $tags Array of tag names
     * @return array Array of tag IDs
     */
    private function get_or_create_tags($tags) {
        $tag_ids = [];
        
        foreach ($tags as $tag) {
            $term = term_exists($tag, 'product_tag');
            
            if (!$term) {
                $term = wp_insert_term($tag, 'product_tag');
            }
            
            if (!is_wp_error($term)) {
                $tag_ids[] = $term['term_id'];
            }
        }
        
        return $tag_ids;
    }
    
    /**
     * Schedule image imports for a product
     *
     * @param int   $product_id   WooCommerce product ID
     * @param array $product_data Printify product data
     */
    private function schedule_image_imports($product_id, $product_data) {
        // Schedule featured image import
        if (!empty($product_data['images'][0]['src'])) {
            as_schedule_single_action(
                time(),
                'wpwps_import_product_image',
                [
                    'product_id' => $product_id,
                    'image_url' => $product_data['images'][0]['src'],
                    'is_featured' => true
                ],
                'wpwps'
            );
        }
        
        // Schedule gallery images import
        if (count($product_data['images']) > 1) {
            for ($i = 1; $i < count($product_data['images']); $i++) {
                as_schedule_single_action(
                    time() + $i, // Stagger imports by 1 second
                    'wpwps_import_product_image',
                    [
                        'product_id' => $product_id,
                        'image_url' => $product_data['images'][$i]['src'],
                        'is_featured' => false
                    ],
                    'wpwps'
                );
            }
        }
        
        // Schedule variant images import
        if (!empty($product_data['variants'])) {
            foreach ($product_data['variants'] as $variant) {
                if (!empty($variant['image_url'])) {
                    as_schedule_single_action(
                        time(),
                        'wpwps_import_variant_image',
                        [
                            'product_id' => $product_id,
                            'variant_id' => $variant['id'],
                            'image_url' => $variant['image_url']
                        ],
                        'wpwps'
                    );
                }
            }
        }
    }
    
    /**
     * Register external product ID with Printify
     *
     * @param string $printify_product_id Printify Product ID
     * @param int    $wc_product_id       WooCommerce Product ID
     */
    private function register_external_product($printify_product_id, $wc_product_id) {
        $response = $this->api->register_external_product(
            $this->shop_id,
            $printify_product_id,
            $wc_product_id
        );
        
        if (is_wp_error($response)) {
            $this->logger->log_error('external_product_registration', $response->get_error_message(), [
                'printify_id' => $printify_product_id,
                'product_id' => $wc_product_id
            ]);
            return;
        }
        
        // Store the response
        update_post_meta($wc_product_id, '_printify_external_registration', $response);
        
        $this->logger->log_success('external_product_registration', __('External product ID registered with Printify', 'wp-woocommerce-printify-sync'), [
            'printify_id' => $printify_product_id,
            'product_id' => $wc_product_id
        ]);
    }
    
    /**
     * Import product image
     *
     * @param int    $product_id  WooCommerce product ID
     * @param string $image_url   Image URL
     * @param bool   $is_featured Whether this is a featured image
     * @return int|WP_Error Attachment ID or error
     */
    public function import_product_image($product_id, $image_url, $is_featured = false) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Download image
        $temp_file = download_url($image_url);
        
        if (is_wp_error($temp_file)) {
            $this->logger->log_error('image_import', $temp_file->get_error_message(), [
                'product_id' => $product_id,
                'image_url' => $image_url
            ]);
            return $temp_file;
        }
        
        // Prepare file array for media_handle_sideload
        $file_array = [
            'name' => basename($image_url),
            'tmp_name' => $temp_file
        ];
        
        // Handle sideload
        $attachment_id = media_handle_sideload($file_array, $product_id);
        
        if (is_wp_error($attachment_id)) {
            @unlink($temp_file);
            $this->logger->log_error('image_import', $attachment_id->get_error_message(), [
                'product_id' => $product_id,
                'image_url' => $image_url
            ]);
            return $attachment_id;
        }
        
        // Set as featured or add to gallery
        if ($is_featured) {
            set_post_thumbnail($product_id, $attachment_id);
        } else {
            $gallery = get_post_meta($product_id, '_product_image_gallery', true);
            $gallery = $gallery ? $gallery . ',' . $attachment_id : $attachment_id;
            update_post_meta($product_id, '_product_image_gallery', $gallery);
        }
        
        // Check if SMUSH Pro is active and optimize the image
        if (function_exists('wp_smush_resize_from_meta_data')) {
            wp_smush_resize_from_meta_data(wp_get_attachment_metadata($attachment_id), $attachment_id);
        }
        
        $this->logger->log_success('image_import', __('Image imported successfully', 'wp-woocommerce-printify-sync'), [
            'product_id' => $product_id,
            'attachment_id' => $attachment_id
        ]);
        
        return $attachment_id;
    }
    
    /**
     * Import variant image
     *
     * @param int    $product_id WooCommerce product ID
     * @param string $variant_id Printify variant ID
     * @param string $image_url  Image URL
     * @return int|WP_Error Attachment ID or error
     */
    public function import_variant_image($product_id, $variant_id, $image_url) {
        // Import the image
        $attachment_id = $this->import_product_image($product_id, $image_url, false);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Find the variation ID by variant ID
        $variation_id = $this->get_variation_id_by_printify_variant_id($product_id, $variant_id);
        
        if ($variation_id) {
            // Set the image for this variation
            update_post_meta($variation_id, '_thumbnail_id', $attachment_id);
        }
        
        return $attachment_id;
    }
    
    /**
     * Get variation ID by Printify variant ID
     *
     * @param int    $product_id WooCommerce product ID
     * @param string $variant_id Printify variant ID
     * @return int|false Variation ID or false
     */
    private function get_variation_id_by_printify_variant_id($product_id, $variant_id) {
        $product = wc_get_product($product_id);
        
        if (!$product || !($product instanceof \WC_Product_Variable)) {
            return false;
        }
        
        $variations = $product->get_children();
        
        foreach ($variations as $variation_id) {
            $printify_variant_id = get_post_meta($variation_id, '_printify_variant_id', true);
            
            if ($printify_variant_id === $variant_id) {
                return $variation_id;
            }
        }
        
        return false;
    }
}
