<?php
/**
 * Product Manager
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Products
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Products;

use ApolloWeb\WPWooCommercePrintifySync\Services\Container;
use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;
use ApolloWeb\WPWooCommercePrintifySync\Services\ApiService;

/**
 * Class ProductManager
 *
 * Handles product sync with Printify
 */
class ProductManager
{
    /**
     * Service container
     *
     * @var Container
     */
    private Container $container;

    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * API service
     *
     * @var ApiService
     */
    private ApiService $api_service;

    /**
     * Constructor
     *
     * @param Container $container Service container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->logger = $container->get('logger');
        
        // Register API service if not already registered
        if (!$container->has('api')) {
            $container->register('api', function () use ($container) {
                return new ApiService($container->get('logger'));
            });
        }
        
        $this->api_service = $container->get('api');
        add_action('add_meta_boxes', [$this, 'addAISuggestionMetaBox']);
        add_action('wp_ajax_wpwps_get_ai_suggestions', [$this, 'ajaxGetAISuggestions']);
    }

    /**
     * Initialize the service
     *
     * @return void
     */
    public function init(): void
    {
        // Register action hooks for product syncing
        add_action('wpwps_sync_products', [$this, 'syncProducts']);
        add_action('wpwps_import_product', [$this, 'importProduct'], 10, 1);
        add_action('wpwps_sync_product', [$this, 'syncProduct'], 10, 1);
        
        // Register webhook handler
        add_action('wpwps_webhook_product_update', [$this, 'handleProductWebhook'], 10, 2);
        
        // Register AJAX handlers
        add_action('wp_ajax_wpwps_import_products', [$this, 'ajaxImportProducts']);
        add_action('wp_ajax_wpwps_sync_all_products', [$this, 'ajaxSyncAllProducts']);
        add_action('wp_ajax_wpwps_get_products', [$this, 'ajaxGetProducts']);
        add_action('wp_ajax_wpwps_get_product_details', [$this, 'ajaxGetProductDetails']);
        
        // Register WP hooks
        add_action('save_post_product', [$this, 'productSaveHook'], 10, 3);
        add_filter('woocommerce_product_data_tabs', [$this, 'addPrintifyProductTab']);
        add_action('woocommerce_product_data_panels', [$this, 'addPrintifyProductTabContent']);
        add_action('woocommerce_process_product_meta', [$this, 'savePrintifyProductTabFields']);
        
        // Add product list columns
        add_filter('manage_edit-product_columns', [$this, 'addProductListColumns']);
        add_action('manage_product_posts_custom_column', [$this, 'renderProductListColumns'], 10, 2);
        add_filter('manage_edit-product_sortable_columns', [$this, 'makeProductListColumnsSortable']);
    }

    /**
     * Import products from Printify
     *
     * @return void
     */
    public function syncProducts(): void
    {
        $this->logger->info('Starting scheduled product sync');
        
        // Check if API credentials are set
        if (!$this->api_service->hasCredentials()) {
            $this->logger->error('API credentials not set');
            return;
        }
        
        // Get the shop ID
        $shop_id = $this->api_service->getShopId();
        
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set');
            return;
        }
        
        // Get products from API
        $endpoint = "shops/{$shop_id}/products.json";
        $products = $this->api_service->get($endpoint);
        
        if (null === $products) {
            $this->logger->error('Failed to get products from API');
            return;
        }
        
        $this->logger->info('Retrieved products from API', ['count' => count($products)]);
        
        // Schedule import for each product
        $action_scheduler = $this->container->get('action_scheduler');
        $imported = 0;
        $updated = 0;
        
        foreach ($products as $product) {
            // Skip products without ID
            if (empty($product['id'])) {
                continue;
            }
            
            // Check if product exists in WooCommerce
            $product_id = $this->getWooProductIdByPrintifyId($product['id']);
            
            if ($product_id) {
                // Schedule sync for existing product
                $action_scheduler->scheduleTask('wpwps_sync_product', [
                    'printify_id' => $product['id'],
                    'woo_product_id' => $product_id,
                ], 0, true);
                $updated++;
            } else {
                // Schedule import for new product
                $action_scheduler->scheduleTask('wpwps_import_product', ['printify_id' => $product['id']], 0, true);
                $imported++;
            }
        }
        
        $this->logger->info('Scheduled product imports and syncs', [
            'import' => $imported,
            'sync' => $updated,
        ]);
    }

    /**
     * Import a single product from Printify
     *
     * @param array $args Arguments
     * @return void
     */
    public function importProduct(array $args): void
    {
        // Check args
        if (empty($args['printify_id'])) {
            $this->logger->error('Missing printify_id in import product arguments');
            return;
        }
        
        $printify_id = $args['printify_id'];
        
        $this->logger->info('Starting product import', ['printify_id' => $printify_id]);
        
        // Check if API credentials are set
        if (!$this->api_service->hasCredentials()) {
            $this->logger->error('API credentials not set');
            return;
        }
        
        // Get the shop ID
        $shop_id = $this->api_service->getShopId();
        
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set');
            return;
        }
        
        // Check if product already exists in WooCommerce
        $existing_product_id = $this->getWooProductIdByPrintifyId($printify_id);
        
        if ($existing_product_id) {
            $this->logger->info('Product already exists, switching to sync', [
                'printify_id' => $printify_id,
                'woo_product_id' => $existing_product_id,
            ]);
            
            // Schedule sync instead
            $action_scheduler = $this->container->get('action_scheduler');
            $action_scheduler->scheduleTask('wpwps_sync_product', [
                'printify_id' => $printify_id,
                'woo_product_id' => $existing_product_id,
            ], 0, true);
            
            return;
        }
        
        // Get product details from API
        $endpoint = "shops/{$shop_id}/products/{$printify_id}.json";
        $printify_product = $this->api_service->get($endpoint);
        
        if (null === $printify_product) {
            $this->logger->error('Failed to get product details from API', ['printify_id' => $printify_id]);
            return;
        }
        
        // Log product details for debugging
        $this->logger->debug('Retrieved product details', [
            'printify_id' => $printify_id,
            'title' => $printify_product['title'] ?? 'Unknown',
        ]);
        
        // Create product in WooCommerce
        $woo_product_id = $this->createWooCommerceProduct($printify_product);
        
        if (!$woo_product_id) {
            $this->logger->error('Failed to create WooCommerce product', ['printify_id' => $printify_id]);
            return;
        }
        
        $this->logger->info('Product imported successfully', [
            'printify_id' => $printify_id,
            'woo_product_id' => $woo_product_id,
        ]);
        
        // Log to database
        $this->logSync('product', $printify_id, 'import', 'success', 'Product imported successfully');
    }

    /**
     * Sync a single product from Printify
     *
     * @param array $args Arguments
     * @return void
     */
    public function syncProduct(array $args): void
    {
        // Check args
        if (empty($args['printify_id'])) {
            $this->logger->error('Missing printify_id in sync product arguments');
            return;
        }
        
        if (empty($args['woo_product_id'])) {
            $this->logger->error('Missing woo_product_id in sync product arguments');
            return;
        }
        
        $printify_id = $args['printify_id'];
        $woo_product_id = $args['woo_product_id'];
        
        $this->logger->info('Starting product sync', [
            'printify_id' => $printify_id,
            'woo_product_id' => $woo_product_id,
        ]);
        
        // Check if API credentials are set
        if (!$this->api_service->hasCredentials()) {
            $this->logger->error('API credentials not set');
            return;
        }
        
        // Get the shop ID
        $shop_id = $this->api_service->getShopId();
        
        if (empty($shop_id)) {
            $this->logger->error('Shop ID not set');
            return;
        }
        
        // Get product details from API
        $endpoint = "shops/{$shop_id}/products/{$printify_id}.json";
        $printify_product = $this->api_service->get($endpoint);
        
        if (null === $printify_product) {
            $this->logger->error('Failed to get product details from API', ['printify_id' => $printify_id]);
            return;
        }
        
        // Log product details for debugging
        $this->logger->debug('Retrieved product details for sync', [
            'printify_id' => $printify_id,
            'woo_product_id' => $woo_product_id,
            'title' => $printify_product['title'] ?? 'Unknown',
        ]);
        
        // Update product in WooCommerce
        $success = $this->updateWooCommerceProduct($woo_product_id, $printify_product);
        
        if (!$success) {
            $this->logger->error('Failed to update WooCommerce product', [
                'printify_id' => $printify_id,
                'woo_product_id' => $woo_product_id,
            ]);
            return;
        }
        
        $this->logger->info('Product synced successfully', [
            'printify_id' => $printify_id,
            'woo_product_id' => $woo_product_id,
        ]);
        
        // Log to database
        $this->logSync('product', $printify_id, 'sync', 'success', 'Product synced successfully');
    }

    /**
     * Process product sync
     */
    public function processSync(array $product_data): bool
    {
        try {
            // Validation
            if (empty($product_data['id'])) {
                $this->logger->error('Invalid product data - missing ID');
                return false;
            }

            // Process sync
            $result = $this->syncProduct($product_data);
            
            if ($result) {
                $this->logger->info('Product synced successfully', [
                    'product_id' => $product_data['id']
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Product sync failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create a new WooCommerce product from Printify data
     *
     * @param array $printify_product Printify product data
     * @return int|false Product ID or false on failure
     */
    private function createWooCommerceProduct(array $printify_product)
    {
        // Check if product has required data
        if (empty($printify_product['id']) || empty($printify_product['title'])) {
            $this->logger->error('Printify product missing required data', [
                'id' => $printify_product['id'] ?? 'N/A',
                'title' => $printify_product['title'] ?? 'N/A',
            ]);
            return false;
        }
        
        // Extract product data
        $title = $printify_product['title'];
        $description = $printify_product['description'] ?? '';
        $variants = $printify_product['variants'] ?? [];
        $images = $printify_product['images'] ?? [];
        $tags = $printify_product['tags'] ?? [];
        $options = $printify_product['options'] ?? [];
        $print_provider_id = $printify_product['print_provider_id'] ?? null;
        $blueprint_id = $printify_product['blueprint_id'] ?? null;
        $print_areas = $printify_product['print_areas'] ?? [];
        
        // Create product
        $product = new \WC_Product_Variable();
        $product->set_name($title);
        $product->set_description($description);
        $product->set_status('publish');
        
        // Set images
        if (!empty($images)) {
            $image_ids = [];
            
            foreach ($images as $index => $image) {
                if (empty($image['src'])) {
                    continue;
                }
                
                // Download image
                $image_id = $this->downloadExternalImage($image['src'], $title . ' - ' . ($index + 1));
                
                if ($image_id) {
                    $image_ids[] = $image_id;
                }
            }
            
            // Set featured image
            if (!empty($image_ids)) {
                $product->set_image_id($image_ids[0]);
                
                // Set gallery images
                if (count($image_ids) > 1) {
                    $product->set_gallery_image_ids(array_slice($image_ids, 1));
                }
            }
        }
        
        // Set tags
        if (!empty($tags)) {
            $tag_ids = [];
            
            foreach ($tags as $tag) {
                $term = term_exists($tag, 'product_tag');
                
                if (!$term) {
                    $term = wp_insert_term($tag, 'product_tag');
                }
                
                if (is_array($term) && !is_wp_error($term)) {
                    $tag_ids[] = $term['term_id'];
                }
            }
            
            if (!empty($tag_ids)) {
                $product->set_tag_ids($tag_ids);
            }
        }
        
        // Build product attributes from options
        $attributes = [];
        
        if (!empty($options)) {
            foreach ($options as $option) {
                if (empty($option['name']) || empty($option['values'])) {
                    continue;
                }
                
                $attribute_name = wc_sanitize_taxonomy_name($option['name']);
                $attribute_slug = 'pa_' . $attribute_name;
                
                // Check if attribute taxonomy exists
                if (!taxonomy_exists($attribute_slug)) {
                    // Create the taxonomy
                    $args = [
                        'name' => $option['name'],
                        'slug' => $attribute_name,
                        'type' => 'select',
                        'order_by' => 'menu_order',
                        'has_archives' => false,
                    ];
                    
                    wc_create_attribute($args);
                    
                    // Register the taxonomy for this request
                    register_taxonomy(
                        $attribute_slug,
                        ['product'],
                        [
                            'hierarchical' => false,
                            'show_ui' => true,
                            'query_var' => true,
                            'rewrite' => false,
                        ]
                    );
                }
                
                // Add attribute values
                $attribute_values = [];
                $attribute_term_ids = [];
                
                foreach ($option['values'] as $value) {
                    if (empty($value['name'])) {
                        continue;
                    }
                    
                    $attribute_values[] = $value['name'];
                    
                    // Add the attribute value as a term
                    $term = term_exists($value['name'], $attribute_slug);
                    
                    if (!$term) {
                        $term = wp_insert_term($value['name'], $attribute_slug);
                    }
                    
                    if (is_array($term) && !is_wp_error($term)) {
                        $attribute_term_ids[] = $term['term_id'];
                    }
                }
                
                // Create attribute object
                $attribute = new \WC_Product_Attribute();
                $attribute->set_id(wc_attribute_taxonomy_id_by_name($attribute_name));
                $attribute->set_name($attribute_slug);
                $attribute->set_options($attribute_term_ids);
                $attribute->set_position(count($attributes));
                $attribute->set_visible(true);
                $attribute->set_variation(true);
                
                $attributes[] = $attribute;
            }
        }
        
        // Set attributes
        if (!empty($attributes)) {
            $product->set_attributes($attributes);
        }
        
        // Save product first to get ID
        $product->save();
        $product_id = $product->get_id();
        
        if (!$product_id) {
            $this->logger->error('Failed to save product');
            return false;
        }
        
        // Add variations
        if (!empty($variants)) {
            $this->createProductVariations($product_id, $variants, $options);
        }
        
        // Set Printify metadata
        update_post_meta($product_id, '_printify_product_id', $printify_product['id']);
        update_post_meta($product_id, '_printify_provider_id', $print_provider_id);
        update_post_meta($product_id, '_printify_blueprint_id', $blueprint_id);
        update_post_meta($product_id, '_printify_last_synced', current_time('mysql'));
        
        // Save print areas as serialized data
        if (!empty($print_areas)) {
            update_post_meta($product_id, '_printify_print_areas', $print_areas);
        }

        // Extract required product data per API spec
        $blueprint_id = $printify_product['blueprint_id'];
        $print_provider_id = $printify_product['print_provider_id'];
        $variants = $printify_product['variants'] ?? [];
        $print_details = $printify_product['print_details'] ?? [];
        $print_areas = $printify_product['print_areas'] ?? [];
        $images = $printify_product['images'] ?? [];

        // Create product
        $product = new \WC_Product_Variable();
        $product->set_name($printify_product['title']);
        $product->set_description($printify_product['description'] ?? '');
        $product->set_status($this->getOption('product_status'));
        
        // Set blueprint and provider metadata
        update_post_meta($product_id, '_printify_blueprint_id', $blueprint_id);
        update_post_meta($product_id, '_printify_provider_id', $print_provider_id);
        update_post_meta($product_id, '_printify_print_details', $print_details);
        update_post_meta($product_id, '_printify_print_areas', $print_areas);

        // Handle variants per API spec
        if (!empty($variants)) {
            foreach ($variants as $variant) {
                $variation = new \WC_Product_Variation();
                $variation->set_parent_id($product_id);
                
                // Set variant data
                $variation->set_regular_price($variant['price']);
                $variation->set_sku($variant['sku']);
                update_post_meta($variation->get_id(), '_printify_variant_id', $variant['id']);
                update_post_meta($variation->get_id(), '_printify_variant_options', $variant['options']);
                
                if (isset($variant['is_enabled'])) {
                    $variation->set_status($variant['is_enabled'] ? 'publish' : 'private');
                }
                
                $variation->save();
            }
        }

        return $product_id;
    }

    /**
     * Create product variations
     *
     * @param int   $product_id Product ID
     * @param array $variants   Variants data
     * @param array $options    Product options/attributes
     * @return void
     */
    private function createProductVariations(int $product_id, array $variants, array $options): void
    {
        // Get product
        $product = wc_get_product($product_id);
        
        if (!$product || !is_a($product, 'WC_Product_Variable')) {
            $this->logger->error('Invalid product for variations', ['product_id' => $product_id]);
            return;
        }
        
        // Build a map of option values to attribute terms
        $attribute_value_map = [];
        
        foreach ($options as $option) {
            if (empty($option['name']) || empty($option['values'])) {
                continue;
            }
            
            $attribute_name = 'pa_' . wc_sanitize_taxonomy_name($option['name']);
            $attribute_value_map[$option['id']] = [
                'name' => $attribute_name,
                'values' => [],
            ];
            
            foreach ($option['values'] as $value) {
                if (empty($value['id']) || empty($value['name'])) {
                    continue;
                }
                
                $attribute_values[] = $value['name'];
                
                // Add the attribute value as a term
                $term = term_exists($value['name'], $attribute_slug);
                
                if (!$term) {
                    $term = wp_insert_term($value['name'], $attribute_slug);
                }
                
                if (is_array($term) && !is_wp_error($term)) {
                    $attribute_term_ids[] = $term['term_id'];
                }
            }
            
            // Create attribute object
            $attribute = new \WC_Product_Attribute();
            $attribute->set_id(wc_attribute_taxonomy_id_by_name($attribute_name));
            $attribute->set_name($attribute_slug);
            $attribute->set_options($attribute_term_ids);
            $attribute->set_position(count($attributes));
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            
            $attributes[] = $attribute;
        }
        
        // Set attributes
        if (!empty($attributes)) {
            $product->set_attributes($attributes);
        }
        
        // Save product first to get ID
        $product->save();
        $product_id = $product->get_id();
        
        if (!$product_id) {
            $this->logger->error('Failed to save product');
            return;
        }
        
        // Add variations
        if (!empty($variants)) {
            $this->createProductVariations($product_id, $variants, $options);
        }
        
        // Set Printify metadata
        update_post_meta($product_id, '_printify_product_id', $printify_product['id']);
        update_post_meta($product_id, '_printify_provider_id', $print_provider_id);
        update_post_meta($product_id, '_printify_blueprint_id', $blueprint_id);
        update_post_meta($product_id, '_printify_last_synced', current_time('mysql'));
        
        // Save print areas as serialized data
        if (!empty($print_areas)) {
            update_post_meta($product_id, '_printify_print_areas', $print_areas);
        }
        
        return $product_id;
    }

    /**
     * Create product variations
     *
     * @param int   $product_id Product ID
     * @param array $variants   Variants data
     * @param array $options    Product options/attributes
     * @return void
     */
    private function createProductVariations(int $product_id, array $variants, array $options): void
    {
        // Get product
        $product = wc_get_product($product_id);
        
        if (!$product || !is_a($product, 'WC_Product_Variable')) {
            $this->logger->error('Invalid product for variations', ['product_id' => $product_id]);
            return;
        }
        
        // Build a map of option values to attribute terms
        $attribute_value_map = [];
        
        foreach ($options as $option) {
            if (empty($option['name']) || empty($option['values'])) {
                continue;
            }
            
            $attribute_name = 'pa_' . wc_sanitize_taxonomy_name($option['name']);
            $attribute_value_map[$option['id']] = [
                'name' => $attribute_name,
                'values' => [],
            ];
            
            foreach ($option['values'] as $value) {
                if (empty($value['id']) || empty($value['name'])) {
                    continue;
                }
                
                $attribute_value_map[$option['id']]['values'][$value['id']] = $value['name'];
            }
        }
        
        // Create variations
        foreach ($variants as $variant) {
            if (empty($variant['id'])) {
                continue;
            }
            
            // Create variation
            $variation = new \WC_Product_Variation();
            $variation->set_parent_id($product_id);
            
            // Set SKU
            if (!empty($variant['sku'])) {
                $variation->set_sku($variant['sku']);
            }
            
            // Set price
            if (isset($variant['price'])) {
                $price = (float) $variant['price'];
                $variation->set_regular_price($price);
                $variation->set_price($price);
            }
            
            // Set cost price as meta
            if (isset($variant['cost'])) {
                $variation->update_meta_data('_printify_cost_price', (float) $variant['cost']);
            }
            
            // Set weight if available
            if (isset($variant['weight'])) {
                $variation->set_weight($variant['weight']);
            }
            
            // Set stock status
            if (isset($variant['is_enabled']) && !$variant['is_enabled']) {
                $variation->set_status('private');
                $variation->set_stock_status('outofstock');
            } else {
                $variation->set_stock_status('instock');
            }
            
            // Set attributes
            $variation_attributes = [];
            
            if (!empty($variant['options'])) {
                foreach ($variant['options'] as $option_id => $value_id) {
                    if (isset($attribute_value_map[$option_id])) {
                        $attribute_name = $attribute_value_map[$option_id]['name'];
                        $attribute_value = $attribute_value_map[$option_id]['values'][$value_id] ?? '';
                        
                        if ($attribute_value) {
                            $variation_attributes[$attribute_name] = $attribute_value;
                        }
                    }
                }
            }
            
            $variation->set_attributes($variation_attributes);
            
            // Save variation
            $variation->save();
            $variation_id = $variation->get_id();
            
            // Store Printify variant ID
            if ($variation_id) {
                update_post_meta($variation_id, '_printify_variant_id', $variant['id']);
            }
        }
        
        // Update product price based on cheapest variant
        $this->updateProductPriceFromVariations($product_id);
    }

    /**
     * Update product price based on cheapest variation
     *
     * @param int $product_id Product ID
     * @return void
     */
    private function updateProductPriceFromVariations(int $product_id): void
    {
        $product = wc_get_product($product_id);
        
        if (!$product || !is_a($product, 'WC_Product_Variable')) {
            return;
        }
        
        $variations = $product->get_available_variations();
        
        if (empty($variations)) {
            return;
        }
        
        $min_price = PHP_INT_MAX;
        $max_price = 0;
        
        foreach ($variations as $variation) {
            $price = (float) $variation['display_price'];
            
            if ($price < $min_price) {
                $min_price = $price;
            }
            
            if ($price > $max_price) {
                $max_price = $price;
            }
        }
        
        if ($min_price < PHP_INT_MAX) {
            update_post_meta($product_id, '_price', $min_price);
            update_post_meta($product_id, '_min_variation_price', $min_price);
            update_post_meta($product_id, '_max_variation_price', $max_price);
        }
    }

    /**
     * Update an existing WooCommerce product with Printify data
     *
     * @param int   $product_id      WooCommerce product ID
     * @param array $printify_product Printify product data
     * @return bool Success
     */
    private function updateWooCommerceProduct(int $product_id, array $printify_product): bool
    {
        // Get product
        $product = wc_get_product($product_id);
        
        if (!$product) {
            $this->logger->error('Product not found', ['product_id' => $product_id]);
            return false;
        }
        
        // Check if it's a variable product
        if (!is_a($product, 'WC_Product_Variable')) {
            $this->logger->error('Product is not variable', ['product_id' => $product_id]);
            return false;
        }
        
        // Check if product has required data
        if (empty($printify_product['id']) || empty($printify_product['title'])) {
            $this->logger->error('Printify product missing required data', [
                'id' => $printify_product['id'] ?? 'N/A',
                'title' => $printify_product['title'] ?? 'N/A',
            ]);
            return false;
        }
        
        // Extract product data
        $title = $printify_product['title'];
        $description = $printify_product['description'] ?? '';
        $variants = $printify_product['variants'] ?? [];
        $images = $printify_product['images'] ?? [];
        $tags = $printify_product['tags'] ?? [];
        $print_provider_id = $printify_product['print_provider_id'] ?? null;
        $blueprint_id = $printify_product['blueprint_id'] ?? null;
        $print_areas = $printify_product['print_areas'] ?? [];
        
        // Update basic product data
        $product->set_name($title);
        $product->set_description($description);
        
        // Update images
        if (!empty($images)) {
            $image_ids = [];
            
            foreach ($images as $index => $image) {
                if (empty($image['src'])) {
                    continue;
                }
                
                // Download image
                $image_id = $this->downloadExternalImage($image['src'], $title . ' - ' . ($index + 1));
                
                if ($image_id) {
                    $image_ids[] = $image_id;
                }
            }
            
            // Set featured image
            if (!empty($image_ids)) {
                $product->set_image_id($image_ids[0]);
                
                // Set gallery images
                if (count($image_ids) > 1) {
                    $product->set_gallery_image_ids(array_slice($image_ids, 1));
                }
            }
        }
        
        // Update tags
        if (!empty($tags)) {
            $tag_ids = [];
            
            foreach ($tags as $tag) {
                $term = term_exists($tag, 'product_tag');
                
                if (!$term) {
                    $term = wp_insert_term($tag, 'product_tag');
                }
                
                if (is_array($term) && !is_wp_error($term)) {
                    $tag_ids[] = $term['term_id'];
                }
            }
            
            if (!empty($tag_ids)) {
                $product->set_tag_ids($tag_ids);
            }
        }
        
        // Save product
        $product->save();
        
        // Update variations
        if (!empty($variants)) {
            $this->updateProductVariations($product_id, $variants);
        }
        
        // Update Printify metadata
        update_post_meta($product_id, '_printify_provider_id', $print_provider_id);
        update_post_meta($product_id, '_printify_blueprint_id', $blueprint_id);
        update_post_meta($product_id, '_printify_last_synced', current_time('mysql'));
        
        // Save print areas as serialized data
        if (!empty($print_areas)) {
            update_post_meta($product_id, '_printify_print_areas', $print_areas);
        }
        
        // Update product price based on cheapest variant
        $this->updateProductPriceFromVariations($product_id);
        
        return true;
    }

    /**
     * Update product variations
     *
     * @param int   $product_id Product ID
     * @param array $variants   Variants data
     * @return void
     */
    private function updateProductVariations(int $product_id, array $variants): void
    {
        // Get product
        $product = wc_get_product($product_id);
        
        if (!$product || !is_a($product, 'WC_Product_Variable')) {
            $this->logger->error('Invalid product for variations', ['product_id' => $product_id]);
            return;
        }
        
        // Get existing variations
        $existing_variations = $product->get_children();
        $variant_map = [];
        
        // Create a map of Printify variant IDs to WooCommerce variation IDs
        foreach ($existing_variations as $variation_id) {
            $printify_variant_id = get_post_meta($variation_id, '_printify_variant_id', true);
            
            if ($printify_variant_id) {
                $variant_map[$printify_variant_id] = $variation_id;
            }
        }
        
        // Update variations
        foreach ($variants as $variant) {
            if (empty($variant['id'])) {
                continue;
            }
            
            $variation_id = $variant_map[$variant['id']] ?? null;
            $variation = $variation_id ? wc_get_product($variation_id) : null;
            
            if ($variation) {
                // Update existing variation
                
                // Set SKU
                if (!empty($variant['sku'])) {
                    $variation->set_sku($variant['sku']);
                }
                
                // Set price
                if (isset($variant['price'])) {
                    $price = (float) $variant['price'];
                    $variation->set_regular_price($price);
                    $variation->set_price($price);
                }
                
                // Set cost price as meta
                if (isset($variant['cost'])) {
                    $variation->update_meta_data('_printify_cost_price', (float) $variant['cost']);
                }
                
                // Set weight if available
                if (isset($variant['weight'])) {
                    $variation->set_weight($variant['weight']);
                }
                
                // Set stock status
                if (isset($variant['is_enabled']) && !$variant['is_enabled']) {
                    $variation->set_status('private');
                    $variation->set_stock_status('outofstock');
                } else {
                    $variation->set_stock_status('instock');
                }
                
                // Save variation
                $variation->save();
            }
            // Note: We don't add new variations here, only update existing ones
            // Adding new variations would require rebuilding the attribute map
        }
    }

    /**
     * Download an external image and attach it to the product
     *
     * @param string $image_url   Image URL
     * @param string $title       Image title
     * @param int    $post_id     Optional post ID to attach the image to
     * @return int|false          Attachment ID or false on failure
     */
    private function downloadExternalImage(string $image_url, string $title, int $post_id = 0)
    {
        // Check if we already have this image
        $image_hash = md5($image_url);
        $existing_attachment = get_posts([
            'post_type' => 'attachment',
            'meta_key' => '_wpwps_image_hash',
            'meta_value' => $image_hash,
            'posts_per_page' => 1,
        ]);
        
        if (!empty($existing_attachment)) {
            return $existing_attachment[0]->ID;
        }
        
        // We need to download the image
        if (!function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        
        // Get file info
        $file_array = [];
        $file_array['name'] = basename($image_url);
        
        // Download file to temp location
        $file_array['tmp_name'] = download_url($image_url);
        
        // If error storing temporarily, return the error
        if (is_wp_error($file_array['tmp_name'])) {
            $this->logger->error('Error downloading image', [
                'url' => $image_url,
                'error' => $file_array['tmp_name']->get_error_message(),
            ]);
            return false;
        }
        
        // Use media_handle_sideload to process the image
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);
        
        // If error storing permanently, clean up and return the error
        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            $this->logger->error('Error processing image', [
                'url' => $image_url,
                'error' => $attachment_id->get_error_message(),
            ]);
            return false;
        }
        
        // Store the image hash for future reference
        update_post_meta($attachment_id, '_wpwps_image_hash', $image_hash);
        
        return $attachment_id;
    }

    /**
     * Get WooCommerce product ID by Printify ID
     *
     * @param string $printify_id Printify product ID
     * @return int|false Product ID or false if not found
     */
    private function getWooProductIdByPrintifyId(string $printify_id)
    {
        global $wpdb;
        
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_printify_product_id' 
                AND meta_value = %s 
                LIMIT 1",
                $printify_id
            )
        );
        
        return $product_id ? (int) $product_id : false;
    }

    /**
     * Log product sync to database
     *
     * @param string $entity_type Entity type
     * @param string $entity_id   Entity ID
     * @param string $action      Action performed
     * @param string $status      Status
     * @param string $message     Message
     * @return void
     */
    private function logSync(string $entity_type, string $entity_id, string $action, string $status, string $message): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpwps_sync_logs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        
        if (!$table_exists) {
            $this->logger->warning('Sync logs table does not exist');
            return;
        }
        
        $wpdb->insert(
            $table_name,
            [
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'action' => $action,
                'status' => $status,
                'message' => $message,
                'created_at' => current_time('mysql', true),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
    }

    /**
     * Handle product webhook from Printify
     *
     * @param string $event  Event name
     * @param array  $payload Webhook payload
     * @return void
     */
    public function handleProductWebhook(string $event, array $payload): void
    {
        // Check if we have a product ID
        if (empty($payload['product']['id'])) {
            $this->logger->error('Product webhook missing product ID', [
                'event' => $event,
                'payload' => $payload,
            ]);
            return;
        }
        
        $printify_id = $payload['product']['id'];
        
        $this->logger->info('Received product webhook', [
            'event' => $event,
            'printify_id' => $printify_id,
        ]);
        
        // Get WooCommerce product ID
        $product_id = $this->getWooProductIdByPrintifyId($printify_id);
        
        if ($product_id) {
            // Product exists, schedule sync
            $action_scheduler = $this->container->get('action_scheduler');
            $action_scheduler->scheduleTask('wpwps_sync_product', [
                'printify_id' => $printify_id,
                'woo_product_id' => $product_id,
            ], 0, true);
            
            $this->logger->info('Scheduled product sync from webhook', [
                'printify_id' => $printify_id,
                'woo_product_id' => $product_id,
            ]);
        } else {
            // Product doesn't exist, schedule import
            $action_scheduler = $this->container->get('action_scheduler');
            $action_scheduler->scheduleTask('wpwps_import_product', ['printify_id' => $printify_id], 0, true);
            
            $this->logger->info('Scheduled product import from webhook', [
                'printify_id' => $printify_id,
            ]);
        }
    }

    /**
     * AJAX handler for importing products
     *
     * @return void
     */
    public function ajaxImportProducts(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Start product sync process
        $this->syncProducts();
        
        wp_send_json_success([
            'message' => __('Product import started. Check the dashboard for progress.', 'wp-woocommerce-printify-sync'),
        ]);
    }

    /**
     * AJAX handler for syncing all products
     *
     * @return void
     */
    public function ajaxSyncAllProducts(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Get all Printify products in WooCommerce
        global $wpdb;
        
        $product_ids = $wpdb->get_results(
            "SELECT post_id, meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' 
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'product' 
                AND post_status = 'publish'
            )"
        );
        
        if (empty($product_ids)) {
            wp_send_json_error([
                'message' => __('No Printify products found to sync', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }
        
        // Schedule sync for each product
        $action_scheduler = $this->container->get('action_scheduler');
        $synced = 0;
        
        foreach ($product_ids as $row) {
            $action_scheduler->scheduleTask('wpwps_sync_product', [
                'printify_id' => $row->meta_value,
                'woo_product_id' => $row->post_id,
            ], 0, true);
            $synced++;
        }
        
        wp_send_json_success([
            'message' => sprintf(
                /* translators: %d: number of products */
                __('Scheduled sync for %d products. Check the dashboard for progress.', 'wp-woocommerce-printify-sync'),
                $synced
            ),
            'count' => $synced,
        ]);
    }

    /**
     * AJAX handler for getting products
     *
     * @return void
     */
    public function ajaxGetProducts(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Get pagination parameters
        $page = isset($_REQUEST['page']) ? absint($_REQUEST['page']) : 1;
        $per_page = isset($_REQUEST['per_page']) ? absint($_REQUEST['per_page']) : 20;
        $search = isset($_REQUEST['search']) ? sanitize_text_field($_REQUEST['search']) : '';
        
        // Get products
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ];
        
        // Add search if provided
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $query = new \WP_Query($args);
        
        // Format products for response
        $products = [];
        
        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            
            if (!$product) {
                continue;
            }
            
            $printify_id = get_post_meta($post->ID, '_printify_product_id', true);
            $last_synced = get_post_meta($post->ID, '_printify_last_synced', true);
            
            $products[] = [
                'id' => $post->ID,
                'title' => $product->get_name(),
                'sku' => $product->get_sku(),
                'price' => $product->get_price(),
                'printify_id' => $printify_id,
                'last_synced' => $last_synced,
                'image' => get_the_post_thumbnail_url($post->ID, 'thumbnail'),
                'edit_url' => get_edit_post_link($post->ID, 'raw'),
                'view_url' => get_permalink($post->ID),
            ];
        }
        
        wp_send_json_success([
            'products' => $products,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'page' => $page,
        ]);
    }

    /**
     * AJAX handler for getting product details
     *
     * @return void
     */
    public function ajaxGetProductDetails(): void
    {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Verify nonce
        check_ajax_referer('wpwps-admin-ajax-nonce', 'nonce');
        
        // Get product ID
        $product_id = isset($_REQUEST['product_id']) ? absint($_REQUEST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(['message' => __('Invalid product ID', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Get product
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error(['message' => __('Product not found', 'wp-woocommerce-printify-sync')]);
            return;
        }
        
        // Get Printify data
        $printify_id = get_post_meta($product_id, '_printify_product_id', true);
        $provider_id = get_post_meta($product_id, '_printify_provider_id', true);
        $blueprint_id = get_post_meta($product_id, '_printify_blueprint_id', true);
        $last_synced = get_post_meta($product_id, '_printify_last_synced', true);
        $print_areas = get_post_meta($product_id, '_printify_print_areas', true);
        
        // Format variations
        $variations = [];
        
        if ($product->is_type('variable')) {
            $product_variations = $product->get_available_variations();
            
            foreach ($product_variations as $variation) {
                $variation_obj = wc_get_product($variation['variation_id']);
                
                if (!$variation_obj) {
                    continue;
                }
                
                $printify_variant_id = get_post_meta($variation['variation_id'], '_printify_variant_id', true);
                $cost_price = get_post_meta($variation['variation_id'], '_printify_cost_price', true);
                
                $variations[] = [
                    'id' => $variation['variation_id'],
                    'attributes' => $variation['attributes'],
                    'price' => $variation['display_price'],
                    'regular_price' => $variation['display_regular_price'],
                    'sku' => $variation_obj->get_sku(),
                    'printify_variant_id' => $printify_variant_id,
                    'cost_price' => $cost_price,
                    'weight' => $variation_obj->get_weight(),
                    'dimensions' => [
                        'length' => $variation_obj->get_length(),
                        'width' => $variation_obj->get_width(),
                        'height' => $variation_obj->get_height(),
                    ],
                    'image' => $variation['image'],
                    'stock_status' => $variation_obj->get_stock_status(),
                ];
            }
        }
        
        // Response data
        $data = [
            'id' => $product_id,
            'title' => $product->get_name(),
            'description' => $product->get_description(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'images' => $this->getProductImages($product),
            'printify_id' => $printify_id,
            'provider_id' => $provider_id,
            'blueprint_id' => $blueprint_id,
            'last_synced' => $last_synced,
            'print_areas' => $print_areas,
            'variations' => $variations,
            'edit_url' => get_edit_post_link($product_id, 'raw'),
            'view_url' => get_permalink($product_id),
        ];
        
        wp_send_json_success($data);
    }

    /**
     * Get product images
     *
     * @param \WC_Product $product Product object
     * @return array
     */
    private function getProductImages(\WC_Product $product): array
    {
        $images = [];
        
        // Add featured image
        $image_id = $product->get_image_id();
        
        if ($image_id) {
            $image = wp_get_attachment_image_src($image_id, 'full');
            
            if ($image) {
                $images[] = [
                    'id' => $image_id,
                    'src' => $image[0],
                    'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
                ];
            }
        }
        
        // Add gallery images
        $gallery_image_ids = $product->get_gallery_image_ids();
        
        foreach ($gallery_image_ids as $gallery_image_id) {
            $image = wp_get_attachment_image_src($gallery_image_id, 'full');
            
            if ($image) {
                $images[] = [
                    'id' => $gallery_image_id,
                    'src' => $image[0],
                    'alt' => get_post_meta($gallery_image_id, '_wp_attachment_image_alt', true),
                ];
            }
        }
        
        return $images;
    }

    /**
     * Hook for product save
     *
     * @param int     $post_id Post ID
     * @param WP_Post $post    Post object
     * @param bool    $update  Whether this is an update
     * @return void
     */
    public function productSaveHook(int $post_id, \WP_Post $post, bool $update): void
    {
        // Skip auto-saves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Check if this is a Printify product
        $printify_id = get_post_meta($post_id, '_printify_product_id', true);
        
        if (!$printify_id) {
            return;
        }
        
        // Update product price based on cheapest variant
        $this->updateProductPriceFromVariations($post_id);
    }

    /**
     * Add Printify tab to WooCommerce product data tabs
     *
     * @param array $tabs Product data tabs
     * @return array
     */
    public function addPrintifyProductTab(array $tabs): array
    {
        $tabs['printify'] = [
            'label' => __('Printify', 'wp-woocommerce-printify-sync'),
            'target' => 'printify_product_data',
            'class' => ['show_if_simple', 'show_if_variable'],
        ];
        
        return $tabs;
    }

    /**
     * Add Printify tab content to WooCommerce product data panels
     *
     * @return void
     */
    public function addPrintifyProductTabContent(): void
    {
        global $post;
        
        // Get Printify data
        $printify_id = get_post_meta($post->ID, '_printify_product_id', true);
        $provider_id = get_post_meta($post->ID, '_printify_provider_id', true);
        $blueprint_id = get_post_meta($post->ID, '_printify_blueprint_id', true);
        $last_synced = get_post_meta($post->ID, '_printify_last_synced', true);
        
        // Output tab content
        ?>
        <div id="printify_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <p class="form-field">
                    <label><?php esc_html_e('Printify Product ID', 'wp-woocommerce-printify-sync'); ?></label>
                    <span><?php echo esc_html($printify_id ?: __('Not a Printify product', 'wp-woocommerce-printify-sync')); ?></span>
                </p>
                
                <?php if ($printify_id) : ?>
                    <p class="form-field">
                        <label><?php esc_html_e('Print Provider ID', 'wp-woocommerce-printify-sync'); ?></label>
                        <span><?php echo esc_html($provider_id); ?></span>
                    </p>
                    
                    <p class="form-field">
                        <label><?php esc_html_e('Blueprint ID', 'wp-woocommerce-printify-sync'); ?></label>
                        <span><?php echo esc_html($blueprint_id); ?></span>
                    </p>
                    
                    <p class="form-field">
                        <label><?php esc_html_e('Last Synced', 'wp-woocommerce-printify-sync'); ?></label>
                        <span><?php echo esc_html($last_synced ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_synced)) : __('Never', 'wp-woocommerce-printify-sync')); ?></span>
                    </p>
                    
                    <p class="form-field">
                        <button type="button" class="button" id="wpwps-sync-product" data-product-id="<?php echo esc_attr($post->ID); ?>" data-printify-id="<?php echo esc_attr($printify_id); ?>">
                            <?php esc_html_e('Sync with Printify', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Save Printify product tab fields
     *
     * @param int $post_id Post ID
     * @return void
     */
    public function savePrintifyProductTabFields(int $post_id): void
    {
        // Nothing to save - the tab only displays information
    }

    /**
     * Add columns to product list
     *
     * @param array $columns Product list columns
     * @return array
     */
    public function addProductListColumns(array $columns): array
    {
        $new_columns = [];
        
        // Insert our column after the product name
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'name') {
                $new_columns['printify'] = __('Printify', 'wp-woocommerce-printify-sync');
            }
        }
        
        return $new_columns;
    }

    /**
     * Render product list column content
     *
     * @param string $column  Column name
     * @param int    $post_id Post ID
     * @return void
     */
    public function renderProductListColumns(string $column, int $post_id): void
    {
        if ($column !== 'printify') {
            return;
        }
        
        $printify_id = get_post_meta($post_id, '_printify_product_id', true);
        
        if ($printify_id) {
            $last_synced = get_post_meta($post_id, '_printify_last_synced', true);
            $last_synced_text = $last_synced ? date_i18n(get_option('date_format'), strtotime($last_synced)) : __('Never', 'wp-woocommerce-printify-sync');
            
            echo '<span class="wpwps-printify-product-tag" title="' . esc_attr(sprintf(__('Printify ID: %s', 'wp-woocommerce-printify-sync'), $printify_id)) . '"><i class="dashicons dashicons-tag"></i> ' . esc_html($printify_id) . '</span>';
            echo '<br><small>' . esc_html(sprintf(__('Last Synced: %s', 'wp-woocommerce-printify-sync'), $last_synced_text)) . '</small>';
        } else {
            echo '<span class="wpwps-not-printify">' . esc_html__('Not a Printify product', 'wp-woocommerce-printify-sync') . '</span>';
        }
    }

    /**
     * Make product list columns sortable
     *
     * @param array $columns Sortable columns
     * @return array
     */
    public function makeProductListColumnsSortable(array $columns): array
    {
        $columns['printify'] = 'printify';
        return $columns;
    }

    public function addAISuggestionMetaBox(): void {
        add_meta_box(
            'wpwps_ai_suggestions',
            __('AI Content Suggestions', 'wp-woocommerce-printify-sync'),
            [$this, 'renderAISuggestionMetaBox'],
            'product',
            'normal',
            'high'
        );
    }

    public function renderAISuggestionMetaBox($post): void {
        wp_nonce_field('wpwps_ai_suggestions', 'wpwps_ai_suggestions_nonce');
        ?>
        <div class="wpwps-ai-suggestions">
            <div class="wpwps-ai-field">
                <button type="button" class="button wpwps-ai-suggest" data-target="title" data-maxlength="140">
                    <?php esc_html_e('Suggest Title', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <div class="wpwps-ai-preview"></div>
            </div>

            <div class="wpwps-ai-field">
                <button type="button" class="button wpwps-ai-suggest" data-target="description">
                    <?php esc_html_e('Suggest Description', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <div class="wpwps-ai-preview"></div>
            </div>

            <div class="wpwps-ai-field">
                <button type="button" class="button wpwps-ai-suggest" data-target="tags" data-maxtags="13" data-maxlength="20">
                    <?php esc_html_e('Suggest Tags', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <div class="wpwps-ai-preview"></div>
            </div>

            <div class="wpwps-ai-field">
                <button type="button" class="button wpwps-ai-suggest" data-target="yoast_seo">
                    <?php esc_html_e('Optimize SEO', 'wp-woocommerce-printify-sync'); ?>
                </button>
                <div class="wpwps-ai-preview"></div>
            </div>
        </div>
        <?php
    }

    public function ajaxGetAISuggestions(): void {
        check_ajax_referer('wpwps_ai_suggestions', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-woocommerce-printify-sync')]);
            return;
        }

        $product_id = absint($_POST['product_id'] ?? 0);
        $target = sanitize_key($_POST['target'] ?? '');
        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(['message' => __('Product not found', 'wp-woocommerce-printify-sync')]);
            return;
        }

        $openai = $this->container->get('openai');
        $product_data = $this->getProductDataForAI($product);

        switch ($target) {
            case 'title':
                $suggestion = $this->getAITitle($openai, $product_data);
                break;
            case 'description':
                $suggestion = $this->getAIDescription($openai, $product_data);
                break;
            case 'tags':
                $suggestion = $this->getAITags($openai, $product_data);
                break;
            case 'yoast_seo':
                $suggestion = $this->getAISEO($openai, $product_data);
                break;
            default:
                wp_send_json_error(['message' => __('Invalid target', 'wp-woocommerce-printify-sync')]);
                return;
        }

        wp_send_json_success(['suggestion' => $suggestion]);
    }

    private function getAITitle($openai, $product_data): string {
        $prompt = sprintf(
            'Create a SEO-optimized product title for Etsy (max 140 characters) for this product: %s',
            json_encode($product_data)
        );

        return $openai->generateContent($prompt);
    }

    private function getAIDescription($openai, $product_data): string {
        $prompt = sprintf(
            'Create a compelling product description optimized for both SEO and Etsy marketplace for this product: %s. Include key features, benefits, and specifications.',
            json_encode($product_data)
        );

        return $openai->generateContent($prompt);
    }

    private function getAITags($openai, $product_data): array {
        $prompt = sprintf(
            'Generate up to 13 SEO-optimized tags for Etsy (max 20 characters each) for this product: %s',
            json_encode($product_data)
        );

        $tags = $openai->generateContent($prompt);
        return array_slice(explode(',', $tags), 0, 13);
    }

    private function getAISEO($openai, $product_data): array {
        if (!class_exists('WPSEO_Meta')) {
            return ['error' => __('Yoast SEO not installed', 'wp-woocommerce-printify-sync')];
        }

        $prompt = sprintf(
            'Generate Yoast SEO metadata for this product: %s. Include meta description (max 156 chars), focus keyphrase, and SEO title (max 60 chars)',
            json_encode($product_data)
        );

        $seo_data = $openai->generateContent($prompt);
        return json_decode($seo_data, true);
    }

    private function getProductDataForAI($product): array {
        return [
            'type' => $product->get_type(),
            'categories' => wp_list_pluck($product->get_category_ids(), 'name'),
            'attributes' => $product->get_attributes(),
            'price' => $product->get_price(),
            'sku' => $product->get_sku(),
        ];
    }

    public function processProduct(array $data): bool
    {
        if (empty($data)) {
            $this->logger->error('Empty product data provided');
            return false;
        }
        
        // Process the product
        try {
            // Processing logic here
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to process product', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }
}
