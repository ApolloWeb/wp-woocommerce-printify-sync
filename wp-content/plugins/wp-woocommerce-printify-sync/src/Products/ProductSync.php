<?php
/**
 * Product synchronization functionality.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Products
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Products;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPIClient;
use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActionSchedulerService;
use ApolloWeb\WPWooCommercePrintifySync\Services\ActivityService;
use WP_Error;

/**
 * Class for syncing products from Printify to WooCommerce.
 */
class ProductSync
{
    /**
     * Printify API client.
     *
     * @var PrintifyAPIClient
     */
    private $api_client;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Action Scheduler service.
     *
     * @var ActionSchedulerService
     */
    private $action_scheduler;

    /**
     * Activity service.
     *
     * @var ActivityService
     */
    private $activity_service;

    /**
     * Constructor.
     *
     * @param PrintifyAPIClient     $api_client       Printify API client.
     * @param Logger                $logger           Logger instance.
     * @param ActionSchedulerService $action_scheduler Action scheduler service.
     * @param ActivityService       $activity_service Activity service.
     */
    public function __construct(
        PrintifyAPIClient $api_client, 
        Logger $logger, 
        ActionSchedulerService $action_scheduler,
        ActivityService $activity_service
    ) {
        $this->api_client = $api_client;
        $this->logger = $logger;
        $this->action_scheduler = $action_scheduler;
        $this->activity_service = $activity_service;
    }

    /**
     * Initialize the product sync.
     *
     * @return void
     */
    public function init()
    {
        // No additional initialization needed at this time
    }

    /**
     * Sync all products from Printify.
     *
     * @return array|WP_Error Result of the sync operation.
     */
    public function syncProducts()
    {
        $this->logger->info('Starting full product sync from Printify');

        // Check if shop ID is set
        $shop_id = $this->api_client->getShopId();
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set, cannot sync products');
            return new WP_Error('missing_shop_id', 'Shop ID is not set. Please configure it in the settings.');
        }

        $page = 1;
        $per_page = 50;
        $total_synced = 0;
        $total_failed = 0;

        // This is a potentially long process, so increase time limit if possible
        if (!ini_get('safe_mode')) {
            set_time_limit(300); // 5 minutes
        }

        do {
            $this->logger->info("Fetching products page {$page} from Printify");
            
            // Get products from Printify
            $response = $this->api_client->getProducts($page, $per_page);
            
            if (is_wp_error($response)) {
                $this->logger->error('Error fetching products: ' . $response->get_error_message());
                return $response;
            }

            // Check if we have products
            if (empty($response['data'])) {
                $this->logger->info('No more products found');
                break;
            }

            $products = $response['data'];
            $this->logger->info('Found ' . count($products) . ' products on page ' . $page);

            // Process each product
            foreach ($products as $product) {
                // Schedule individual product sync
                $this->action_scheduler->scheduleSyncProduct($product['id']);
                $total_synced++;
            }

            // Check if there are more pages
            $total_pages = isset($response['last_page']) ? (int) $response['last_page'] : 1;
            $page++;
        } while ($page <= $total_pages);

        $this->logger->info("Finished scheduling sync for {$total_synced} products. Failed: {$total_failed}");

        $this->activity_service->log('product_sync', sprintf(
            __('Synced %d products from Printify', 'wp-woocommerce-printify-sync'),
            $total_synced
        ), [
            'total_synced' => $total_synced,
            'total_failed' => $total_failed,
            'time' => current_time('mysql')
        ]);

        return [
            'success' => true,
            'total_synced' => $total_synced,
            'total_failed' => $total_failed,
        ];
    }

    /**
     * Sync a single product from Printify.
     *
     * @param string $printify_product_id Printify product ID.
     * @return array|WP_Error Result of the sync operation.
     */
    public function syncSingleProduct($printify_product_id)
    {
        $this->logger->info("Syncing product {$printify_product_id} from Printify");

        // Get the product details from Printify
        $product_data = $this->api_client->getProduct($printify_product_id);
        
        if (is_wp_error($product_data)) {
            $this->logger->error("Error fetching product {$printify_product_id}: " . $product_data->get_error_message());
            return $product_data;
        }

        // Check if the product exists in WooCommerce
        $wc_product_id = $this->getWooCommerceProductIdByPrintifyId($printify_product_id);
        
        if ($wc_product_id) {
            // Update existing product
            $result = $this->updateWooCommerceProduct($wc_product_id, $product_data);
        } else {
            // Create new product
            $result = $this->createWooCommerceProduct($product_data);
        }

        if (is_wp_error($result)) {
            $this->logger->error("Error syncing product {$printify_product_id}: " . $result->get_error_message());
            return $result;
        }

        $this->logger->info("Successfully synced product {$printify_product_id}");

        $this->activity_service->log('product_sync', sprintf(
            __('Synced product "%s" from Printify', 'wp-woocommerce-printify-sync'),
            $product_data['title']
        ), [
            'product_id' => $result,
            'printify_id' => $printify_product_id,
            'title' => $product_data['title'],
            'time' => current_time('mysql')
        ]);
        
        return [
            'success' => true,
            'product_id' => $result,
            'message' => sprintf(
                __('Product %s successfully synced.', 'wp-woocommerce-printify-sync'),
                $product_data['title']
            ),
        ];
    }

    /**
     * Get WooCommerce product ID by Printify product ID.
     *
     * @param string $printify_product_id Printify product ID.
     * @return int|false WooCommerce product ID or false if not found.
     */
    private function getWooCommerceProductIdByPrintifyId($printify_product_id)
    {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_printify_product_id' AND meta_value = %s LIMIT 1",
            $printify_product_id
        );
        
        $product_id = $wpdb->get_var($query);
        
        return $product_id ? (int) $product_id : false;
    }

    /**
     * Create a new WooCommerce product from Printify data.
     *
     * @param array $product_data Printify product data.
     * @return int|WP_Error WooCommerce product ID or error.
     */
    private function createWooCommerceProduct($product_data)
    {
        $this->logger->info("Creating new WooCommerce product: {$product_data['title']}");
        
        // Ensure WooCommerce's Product Data Store HPOS support
        $product = new \WC_Product_Variable();
        
        // Set basic product data
        $product->set_name($product_data['title']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_description($product_data['description']);
        $product->set_short_description(wp_trim_words($product_data['description'], 25));
        
        // Set SKU if available
        if (!empty($product_data['sku'])) {
            $product->set_sku($product_data['sku']);
        }
        
        // Set product tags
        if (!empty($product_data['tags'])) {
            wp_set_object_terms($product->get_id(), $product_data['tags'], 'product_tag');
        }
        
        // Set product categories
        if (!empty($product_data['print_provider_id'])) {
            // Create and set categories based on product type
            $this->setupProductCategories($product, $product_data);
        }
        
        // Save the product to get an ID
        $product_id = $product->save();
        
        if (!$product_id) {
            return new WP_Error('product_creation_failed', __('Failed to create WooCommerce product.', 'wp-woocommerce-printify-sync'));
        }
        
        // Add Printify metadata
        update_post_meta($product_id, '_printify_product_id', $product_data['id']);
        update_post_meta($product_id, '_printify_provider_id', $product_data['print_provider_id']);
        update_post_meta($product_id, '_printify_last_synced', current_time('mysql'));
        
        // Import product images
        $this->importProductImages($product_id, $product_data);
        
        // Create product variations
        $this->createProductVariations($product_id, $product_data);
        
        $this->logger->info("Created WooCommerce product ID: {$product_id}");
        
        return $product_id;
    }

    /**
     * Update an existing WooCommerce product with Printify data.
     *
     * @param int   $product_id   WooCommerce product ID.
     * @param array $product_data Printify product data.
     * @return int|WP_Error WooCommerce product ID or error.
     */
    private function updateWooCommerceProduct($product_id, $product_data)
    {
        $this->logger->info("Updating WooCommerce product ID: {$product_id}");
        
        // Get the WooCommerce product
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new WP_Error('product_not_found', __('WooCommerce product not found.', 'wp-woocommerce-printify-sync'));
        }
        
        // Update basic product data
        $product->set_name($product_data['title']);
        $product->set_description($product_data['description']);
        $product->set_short_description(wp_trim_words($product_data['description'], 25));
        
        // Update SKU if available
        if (!empty($product_data['sku'])) {
            $product->set_sku($product_data['sku']);
        }
        
        // Update product tags
        if (!empty($product_data['tags'])) {
            wp_set_object_terms($product_id, $product_data['tags'], 'product_tag');
        }
        
        // Update product categories
        if (!empty($product_data['print_provider_id'])) {
            $this->setupProductCategories($product, $product_data);
        }
        
        // Save the product changes
        $product->save();
        
        // Update Printify metadata
        update_post_meta($product_id, '_printify_provider_id', $product_data['print_provider_id']);
        update_post_meta($product_id, '_printify_last_synced', current_time('mysql'));
        
        // Update product images
        $this->importProductImages($product_id, $product_data);
        
        // Update product variations
        $this->updateProductVariations($product_id, $product_data);
        
        $this->logger->info("Updated WooCommerce product ID: {$product_id}");
        
        return $product_id;
    }

    /**
     * Set up product categories based on Printify data.
     *
     * @param \WC_Product $product      WooCommerce product.
     * @param array       $product_data Printify product data.
     * @return void
     */
    private function setupProductCategories($product, $product_data)
    {
        // Example: Map Printify product types to WooCommerce categories
        $categories = [];
        
        if (!empty($product_data['print_provider_id'])) {
            // Get provider info to create primary category
            $provider_id = $product_data['print_provider_id'];
            $provider_name = $this->getProviderName($provider_id);
            
            if ($provider_name) {
                $categories[] = $provider_name;
            }
        }
        
        if (!empty($product_data['blueprint_id'])) {
            // Get blueprint info (product type) to create subcategory
            $blueprint_id = $product_data['blueprint_id'];
            $blueprint_name = $this->getBlueprintName($blueprint_id);
            
            if ($blueprint_name) {
                $categories[] = $blueprint_name;
            }
        }
        
        // Create category hierarchy
        $category_ids = [];
        $parent_id = 0;
        
        foreach ($categories as $category_name) {
            // Check if the category exists
            $existing_term = get_term_by('name', $category_name, 'product_cat');
            
            if ($existing_term) {
                $term_id = $existing_term->term_id;
            } else {
                // Create a new category
                $term = wp_insert_term($category_name, 'product_cat', ['parent' => $parent_id]);
                
                if (is_wp_error($term)) {
                    continue;
                }
                
                $term_id = $term['term_id'];
            }
            
            $category_ids[] = $term_id;
            $parent_id = $term_id;
        }
        
        // Assign categories to the product
        if (!empty($category_ids)) {
            wp_set_object_terms($product->get_id(), $category_ids, 'product_cat');
        }
    }

    /**
     * Import product images from Printify.
     *
     * @param int   $product_id   WooCommerce product ID.
     * @param array $product_data Printify product data.
     * @return void
     */
    private function importProductImages($product_id, $product_data)
    {
        if (empty($product_data['images'])) {
            $this->logger->info("No images to import for product ID: {$product_id}");
            return;
        }
        
        $this->logger->info("Importing " . count($product_data['images']) . " images for product ID: {$product_id}");
        
        $product = wc_get_product($product_id);
        $attachment_ids = [];
        
        // Process each image
        foreach ($product_data['images'] as $index => $image_data) {
            $image_url = $image_data['src'];
            $this->logger->info("Importing image: {$image_url}");
            
            // Download and attach the image to the product
            $attachment_id = $this->downloadAndAttachImage($product_id, $image_url);
            
            if (!is_wp_error($attachment_id)) {
                $attachment_ids[] = $attachment_id;
                
                // Set as product gallery image
                if ($index === 0) {
                    // Set as featured image
                    set_post_thumbnail($product_id, $attachment_id);
                }
            } else {
                $this->logger->error("Failed to import image: {$image_url} - " . $attachment_id->get_error_message());
            }
        }
        
        // Update product gallery
        if (!empty($attachment_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $attachment_ids));
        }
    }

    /**
     * Download and attach image to product.
     *
     * @param int    $product_id WooCommerce product ID.
     * @param string $image_url  Image URL.
     * @return int|WP_Error Attachment ID or error.
     */
    private function downloadAndAttachImage($product_id, $image_url)
    {
        // Require WordPress media handling functions
        if (!function_exists('media_sideload_image')) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }
        
        // Get the file name from the URL
        $file_name = basename(parse_url($image_url, PHP_URL_PATH));
        
        // Check if we already have this image for this product (to avoid duplicates)
        $existing_attachment_id = $this->getExistingAttachment($product_id, $file_name);
        
        if ($existing_attachment_id) {
            return $existing_attachment_id;
        }
        
        // Download the image
        $attachment_id = media_sideload_image($image_url, $product_id, null, 'id');
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Save the original URL with the attachment for future reference
        update_post_meta($attachment_id, '_printify_original_url', $image_url);
        
        return $attachment_id;
    }

    /**
     * Check if an image is already attached to the product.
     *
     * @param int    $product_id WooCommerce product ID.
     * @param string $file_name  Image file name.
     * @return int|false Attachment ID or false if not found.
     */
    private function getExistingAttachment($product_id, $file_name)
    {
        // Get all attachments for this product
        $attachments = get_attached_media('image', $product_id);
        
        foreach ($attachments as $attachment) {
            if (basename($attachment->guid) === $file_name) {
                return $attachment->ID;
            }
        }
        
        return false;
    }

    /**
     * Create product variations from Printify data.
     *
     * @param int   $product_id   WooCommerce product ID.
     * @param array $product_data Printify product data.
     * @return void
     */
    private function createProductVariations($product_id, $product_data)
    {
        if (empty($product_data['variants'])) {
            $this->logger->info("No variants to create for product ID: {$product_id}");
            return;
        }
        
        $this->logger->info("Creating " . count($product_data['variants']) . " variations for product ID: {$product_id}");
        
        $product = wc_get_product($product_id);
        
        // Extract and create attributes
        $attributes = $this->extractProductAttributes($product_data['variants']);
        
        // Set attributes to the product
        foreach ($attributes as $attribute_name => $attribute_data) {
            $attribute = new \WC_Product_Attribute();
            $attribute->set_name($attribute_name);
            $attribute->set_options($attribute_data['terms']);
            $attribute->set_position(0);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            
            $product_attributes[] = $attribute;
        }
        
        $product->set_attributes($product_attributes);
        $product->save();
        
        // Create variations
        foreach ($product_data['variants'] as $variant) {
            $variation = new \WC_Product_Variation();
            $variation->set_parent_id($product_id);
            
            // Set variation attributes
            $variation_attributes = [];
            foreach ($attributes as $attribute_name => $attribute_data) {
                $attr_key = strtolower($attribute_name);
                if (isset($variant[$attr_key])) {
                    $variation_attributes['attribute_' . sanitize_title($attribute_name)] = sanitize_title($variant[$attr_key]);
                }
            }
            $variation->set_attributes($variation_attributes);
            
            // Set SKU
            if (!empty($variant['sku'])) {
                $variation->set_sku($variant['sku']);
            }
            
            // Set prices
            if (isset($variant['price'])) {
                $variation->set_regular_price($variant['price']);
                $variation->set_price($variant['price']);
            }
            
            // Set stock status based on availability
            $is_enabled = isset($variant['is_enabled']) ? (bool) $variant['is_enabled'] : true;
            $is_available = isset($variant['is_available']) ? (bool) $variant['is_available'] : true;
            
            if ($is_enabled && $is_available) {
                $variation->set_stock_status('instock');
                $variation->set_stock_quantity(10); // Default stock
                $variation->set_manage_stock(true);
            } else {
                $variation->set_stock_status('outofstock');
                $variation->set_stock_quantity(0);
                $variation->set_manage_stock(true);
            }
            
            // Save Printify metadata
            $variation->add_meta_data('_printify_variant_id', $variant['id'], true);
            $variation->add_meta_data('_printify_cost_price', isset($variant['cost']) ? $variant['cost'] : 0, true);
            
            // Save the variation
            $variation->save();
            
            // Track variant ID mapping
            $variant_mapping = get_post_meta($product_id, '_printify_variant_ids', true) ?: [];
            $variant_mapping[$variant['id']] = $variation->get_id();
            update_post_meta($product_id, '_printify_variant_ids', $variant_mapping);
        }
    }

    /**
     * Update product variations from Printify data.
     *
     * @param int   $product_id   WooCommerce product ID.
     * @param array $product_data Printify product data.
     * @return void
     */
    private function updateProductVariations($product_id, $product_data)
    {
        if (empty($product_data['variants'])) {
            $this->logger->info("No variants to update for product ID: {$product_id}");
            return;
        }
        
        $this->logger->info("Updating variations for product ID: {$product_id}");
        
        // Get existing variations
        $existing_variations = wc_get_products([
            'parent' => $product_id,
            'type'   => 'variation',
            'limit'  => -1,
        ]);
        
        // Get existing variant mapping
        $variant_mapping = get_post_meta($product_id, '_printify_variant_ids', true) ?: [];
        
        // Extract and update attributes
        $attributes = $this->extractProductAttributes($product_data['variants']);
        $product = wc_get_product($product_id);
        
        // Set attributes to the product
        $product_attributes = [];
        foreach ($attributes as $attribute_name => $attribute_data) {
            $attribute = new \WC_Product_Attribute();
            $attribute->set_name($attribute_name);
            $attribute->set_options($attribute_data['terms']);
            $attribute->set_position(0);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            
            $product_attributes[] = $attribute;
        }
        
        $product->set_attributes($product_attributes);
        $product->save();
        
        // Process each variant
        foreach ($product_data['variants'] as $variant) {
            // Check if we have an existing mapping for this variant
            $variation_id = isset($variant_mapping[$variant['id']]) ? $variant_mapping[$variant['id']] : null;
            $variation = null;
            
            if ($variation_id) {
                // Get the existing variation
                $variation = wc_get_product($variation_id);
            }
            
            if (!$variation) {
                // Create a new variation
                $variation = new \WC_Product_Variation();
                $variation->set_parent_id($product_id);
            }
            
            // Set variation attributes
            $variation_attributes = [];
            foreach ($attributes as $attribute_name => $attribute_data) {
                $attr_key = strtolower($attribute_name);
                if (isset($variant[$attr_key])) {
                    $variation_attributes['attribute_' . sanitize_title($attribute_name)] = sanitize_title($variant[$attr_key]);
                }
            }
            $variation->set_attributes($variation_attributes);
            
            // Set SKU
            if (!empty($variant['sku'])) {
                $variation->set_sku($variant['sku']);
            }
            
            // Set prices
            if (isset($variant['price'])) {
                $variation->set_regular_price($variant['price']);
                $variation->set_price($variant['price']);
            }
            
            // Set cost price and retail price
            if (isset($variant['cost'])) {
                $variation->update_meta_data('_printify_cost_price', $variant['cost']);
                // Store cost breakdown if available
                if (isset($variant['cost_breakdown'])) {
                    $variation->update_meta_data('_printify_cost_breakdown', $variant['cost_breakdown']);
                }
            }
            
            if (isset($variant['price'])) {
                $variation->set_regular_price($variant['price']);
                $variation->set_price($variant['price']);
            }
            
            // Set stock status based on availability
            $is_enabled = isset($variant['is_enabled']) ? (bool) $variant['is_enabled'] : true;
            $is_available = isset($variant['is_available']) ? (bool) $variant['is_available'] : true;
            
            if ($is_enabled && $is_available) {
                $variation->set_stock_status('instock');
                $variation->set_stock_quantity(10); // Default stock
                $variation->set_manage_stock(true);
            } else {
                $variation->set_stock_status('outofstock');
                $variation->set_stock_quantity(0);
                $variation->set_manage_stock(true);
            }
            
            // Save Printify metadata
            $variation->update_meta_data('_printify_variant_id', $variant['id']);
            $variation->update_meta_data('_printify_cost_price', isset($variant['cost']) ? $variant['cost'] : 0);
            
            // Save the variation
            $variation_id = $variation->save();
            
            // Update variant mapping
            $variant_mapping[$variant['id']] = $variation_id;
        }
        
        // Update the variant mapping
        update_post_meta($product_id, '_printify_variant_ids', $variant_mapping);
        
        // Handle variations that no longer exist in Printify
        $printify_variant_ids = array_column($product_data['variants'], 'id');
        
        foreach ($existing_variations as $existing_variation) {
            $printify_variant_id = $existing_variation->get_meta('_printify_variant_id');
            
            if (!in_array($printify_variant_id, $printify_variant_ids)) {
                // Variant no longer exists in Printify
                wp_delete_post($existing_variation->get_id(), true);
                unset($variant_mapping[$printify_variant_id]);
            }
        }
        
        // Update the variant mapping again after removing deleted variants
        update_post_meta($product_id, '_printify_variant_ids', $variant_mapping);
    }

    /**
     * Extract product attributes from variants.
     *
     * @param array $variants Printify variants.
     * @return array Attributes array.
     */
    private function extractProductAttributes($variants)
    {
        $attributes = [];
        
        foreach ($variants as $variant) {
            // Common attributes to extract
            $attribute_keys = ['size', 'color', 'material', 'style'];
            
            foreach ($attribute_keys as $key) {
                if (isset($variant[$key]) && !empty($variant[$key])) {
                    $attribute_name = ucfirst($key);
                    
                    if (!isset($attributes[$attribute_name])) {
                        $attributes[$attribute_name] = [
                            'terms' => [],
                        ];
                    }
                    
                    if (!in_array($variant[$key], $attributes[$attribute_name]['terms'])) {
                        $attributes[$attribute_name]['terms'][] = $variant[$key];
                    }
                }
            }
        }
        
        return $attributes;
    }

    /**
     * Get provider name by ID.
     *
     * @param int $provider_id Provider ID.
     * @return string Provider name.
     */
    private function getProviderName($provider_id)
    {
        static $providers_cache = [];
        
        // Check if we have the provider in cache already
        if (isset($providers_cache[$provider_id])) {
            return $providers_cache[$provider_id];
        }
        
        // Try to get from wp_options first to avoid API calls
        $saved_providers = get_option('wpwps_print_providers', []);
        if (!empty($saved_providers[$provider_id])) {
            $providers_cache[$provider_id] = $saved_providers[$provider_id];
            return $saved_providers[$provider_id];
        }
        
        // Need to fetch from API
        $provider_data = $this->api_client->getPrintProvider($provider_id);
        
        if (is_wp_error($provider_data)) {
            $this->logger->error("Error fetching provider {$provider_id}: " . $provider_data->get_error_message());
            return 'Unknown Provider';
        }
        
        if (!empty($provider_data['name'])) {
            // Update cache and save to options
            $providers_cache[$provider_id] = $provider_data['name'];
            $saved_providers[$provider_id] = $provider_data['name'];
            update_option('wpwps_print_providers', $saved_providers);
            
            return $provider_data['name'];
        }
        
        return 'Print Provider ' . $provider_id;
    }

    /**
     * Get blueprint name by ID.
     *
     * @param int $blueprint_id Blueprint ID.
     * @return string Blueprint name.
     */
    private function getBlueprintName($blueprint_id)
    {
        static $blueprints_cache = [];
        
        // Check if we have the blueprint in cache already
        if (isset($blueprints_cache[$blueprint_id])) {
            return $blueprints_cache[$blueprint_id];
        }
        
        // Try to get from wp_options first to avoid API calls
        $saved_blueprints = get_option('wpwps_blueprints', []);
        if (!empty($saved_blueprints[$blueprint_id])) {
            $blueprints_cache[$blueprint_id] = $saved_blueprints[$blueprint_id];
            return $saved_blueprints[$blueprint_id];
        }
        
        // Try to fetch from the Printify API using the new catalog endpoints
        $blueprint_data = $this->api_client->getCatalogBlueprint($blueprint_id);
        
        if (!is_wp_error($blueprint_data) && !empty($blueprint_data['title'])) {
            $blueprint_name = $blueprint_data['title'];
            
            // Update cache and save to options
            $blueprints_cache[$blueprint_id] = $blueprint_name;
            $saved_blueprints[$blueprint_id] = $blueprint_name;
            update_option('wpwps_blueprints', $saved_blueprints);
            
            return $blueprint_name;
        }
        
        // Fall back to default mapping if API fetch failed
        $default_blueprints = [
            1 => 'T-Shirts',
            2 => 'Hoodies',
            3 => 'Mugs',
            4 => 'Posters',
            5 => 'Canvas',
            6 => 'Phone Cases',
            7 => 'Tote Bags',
            8 => 'Pillows',
            9 => 'Stickers',
            10 => 'Accessories',
            // Add more blueprints as needed
        ];
        
        $blueprint_name = isset($default_blueprints[$blueprint_id]) 
            ? $default_blueprints[$blueprint_id] 
            : 'Product ' . $blueprint_id;
        
        // Log error if API fetch failed
        if (is_wp_error($blueprint_data)) {
            $this->logger->error("Error fetching blueprint {$blueprint_id}: " . $blueprint_data->get_error_message());
        }
        
        // Update cache
        $blueprints_cache[$blueprint_id] = $blueprint_name;
        $saved_blueprints[$blueprint_id] = $blueprint_name;
        update_option('wpwps_blueprints', $saved_blueprints);
        
        return $blueprint_name;
    }

    private function updateProductPrices($product, $printify_data)
    {
        $price_calculator = new PriceCalculator($this->logger);
        
        foreach ($printify_data['variants'] as $variant) {
            $retail_price = $price_calculator->calculateRetailPrice(
                $variant['cost'],
                $printify_data['print_provider_id'],
                $printify_data['type']
            );
            
            // Store both prices
            update_post_meta($product->get_id(), '_printify_cost_' . $variant['id'], $variant['cost']);
            update_post_meta($product->get_id(), '_regular_price', $retail_price);
            
            // Store markup info for reference
            update_post_meta($product->get_id(), '_printify_markup_provider', $printify_data['print_provider_id']);
            update_post_meta($product->get_id(), '_printify_product_type', $printify_data['type']);
            
            $this->logger->info(sprintf(
                'Updated prices for variant %s: Cost %.2f, Retail %.2f',
                $variant['id'],
                $variant['cost'],
                $retail_price
            ));
        }
    }

    /**
     * Create or update product variation attributes.
     *
     * @param int   $product_id    WooCommerce product ID.
     * @param array $printify_data Printify product data.
     * @return array Array of attribute ids and names.
     */
    private function setupProductAttributes($product_id, $printify_data)
    {
        if (empty($printify_data['variants'])) {
            return [];
        }
        
        // Extract all possible attributes
        $attributes = [];
        $attribute_taxonomies = [];
        
        // Get existing product attributes
        $product = wc_get_product($product_id);
        $existing_attributes = $product->get_attributes();
        
        // Collect all variant properties
        foreach ($printify_data['variants'] as $variant) {
            if (!empty($variant['options'])) {
                foreach ($variant['options'] as $option_name => $option_value) {
                    if (!isset($attributes[$option_name])) {
                        $attributes[$option_name] = [];
                    }
                    
                    if (!empty($option_value) && !in_array($option_value, $attributes[$option_name])) {
                        $attributes[$option_name][] = $option_value;
                    }
                }
            }
        }
        
        // Define attribute data
        $attribute_data = [];
        
        // Process each attribute
        foreach ($attributes as $name => $values) {
            if (empty($values)) {
                continue;
            }
            
            // Format attribute name
            $attribute_name = $this->formatAttributeName($name);
            $attribute_label = $this->formatAttributeLabel($name);
            
            // Check if attribute taxonomy exists
            $attribute_taxonomy_name = wc_attribute_taxonomy_name($attribute_name);
            $attribute_taxonomy_id = wc_attribute_taxonomy_id_by_name($attribute_name);
            
            // Create attribute taxonomy if it doesn't exist
            if (!$attribute_taxonomy_id) {
                $taxonomy_data = [
                    'name' => $attribute_label,
                    'slug' => $attribute_name,
                    'type' => 'select',
                    'order_by' => 'menu_order',
                    'has_archives' => false,
                ];
                
                $attribute_taxonomy_id = wc_create_attribute($taxonomy_data);
                
                if (is_wp_error($attribute_taxonomy_id)) {
                    $this->logger->error('Error creating attribute taxonomy: ' . $attribute_taxonomy_id->get_error_message());
                    continue;
                }
                
                // Register the taxonomy immediately
                register_taxonomy(
                    $attribute_taxonomy_name,
                    ['product'],
                    [
                        'labels' => [
                            'name' => $attribute_label,
                        ],
                        'hierarchical' => false,
                        'show_ui' => false,
                        'query_var' => true,
                        'rewrite' => false,
                    ]
                );
                
                $this->logger->info('Created new attribute taxonomy', [
                    'name' => $attribute_name,
                    'id' => $attribute_taxonomy_id,
                ]);
            }
            
            $attribute_taxonomies[$attribute_name] = $attribute_taxonomy_name;
            
            // Ensure all terms exist
            $term_ids = [];
            
            foreach ($values as $value) {
                $term_name = sanitize_text_field($value);
                $term = get_term_by('name', $term_name, $attribute_taxonomy_name);
                
                if (!$term) {
                    $term = wp_insert_term($term_name, $attribute_taxonomy_name);
                    
                    if (is_wp_error($term)) {
                        $this->logger->error('Error creating attribute term: ' . $term->get_error_message());
                        continue;
                    }
                    
                    $term_id = $term['term_id'];
                } else {
                    $term_id = $term->term_id;
                }
                
                $term_ids[] = $term_id;
            }
            
            // Create attribute data
            $attribute_data[$attribute_taxonomy_name] = [
                'name' => $attribute_taxonomy_name,
                'value' => '',
                'is_visible' => true,
                'is_variation' => true,
                'is_taxonomy' => true,
                'position' => count($attribute_data),
            ];
            
            // Assign terms to product
            wp_set_object_terms($product_id, $term_ids, $attribute_taxonomy_name);
        }
        
        // Save attributes to product
        if (!empty($attribute_data)) {
            $product->set_attributes($attribute_data);
            $product->save();
        }
        
        return $attribute_taxonomies;
    }

    /**
     * Format attribute name for WooCommerce.
     *
     * @param string $name Attribute name.
     * @return string Formatted attribute name.
     */
    private function formatAttributeName($name)
    {
        return wc_sanitize_taxonomy_name(strtolower(str_replace(' ', '-', $name)));
    }

    /**
     * Format attribute label for display.
     *
     * @param string $name Attribute name.
     * @return string Formatted attribute label.
     */
    private function formatAttributeLabel($name)
    {
        return ucfirst(str_replace(['_', '-'], ' ', $name));
    }
}
