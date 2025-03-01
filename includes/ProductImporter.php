<?php
namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Main product importer class
 */
class ProductImporter {
    /**
     * API instance
     *
     * @var Api
     */
    private $api;
    
    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;
    
    /**
     * Selected shop ID
     *
     * @var int
     */
    private $shop_id;
    
    /**
     * Helper instances
     */
    private $imagesHelper;
    private $tagsHelper;
    private $categoriesHelper;
    private $variantsHelper;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new Api();
        $this->logger = new Logger('product-importer');
        $this->shop_id = get_option('wpwps_selected_shop');
        
        // Initialize helpers
        $this->imagesHelper = new Helpers\ImagesHelper();
        $this->tagsHelper = new Helpers\TagsHelper();
        $this->categoriesHelper = new Helpers\CategoriesHelper();
        $this->variantsHelper = new Helpers\VariantsHelper();
        
        // Register action hooks for import
        add_action('wpwps_import_product', [$this, 'importSingleProduct'], 10, 2);
    }
    
    /**
     * Start the import process
     *
     * @return bool Success status
     */
    public function startImport() {
        if (empty($this->shop_id)) {
            $this->logger->log('No shop selected for import');
            return false;
        }
        
        // Get total product count to calculate progress
        $total_products = $this->api->getTotalProductCount($this->shop_id);
        
        if (is_wp_error($total_products)) {
            $this->logger->log('Failed to get total product count: ' . $total_products->get_error_message());
            return false;
        }
        
        // Store total products for progress tracking
        update_option('wpwps_import_total_products', $total_products);
        update_option('wpwps_import_processed_products', 0);
        update_option('wpwps_import_in_progress', 'yes');
        update_option('wpwps_import_started', current_time('timestamp'));
        
        $this->logger->log("Starting import of {$total_products} products from shop {$this->shop_id}");
        
        // Schedule import in chunks
        return $this->scheduleImportChunks();
    }
    
    /**
     * Schedule import in chunks
     *
     * @param int $page Starting page
     * @param int $limit Products per page
     * @return bool Success status
     */
    public function scheduleImportChunks($page = 1, $limit = 20) {
        // Fetch products for the current page
        $products = $this->api->getProducts($this->shop_id, $page, $limit);
        
        if (is_wp_error($products)) {
            $this->logger->log('Failed to fetch products: ' . $products->get_error_message());
            return false;
        }
        
        // Schedule each product for import
        foreach ($products['data'] as $product) {
            as_schedule_single_action(
                time(), 
                'wpwps_import_product',
                [$this->shop_id, $product['id']]
            );
        }
        
        // Check if there are more pages to process
        $pagination = $products['pagination'] ?? [];
        if (($pagination['current_page'] ?? 0) < ($pagination['total_pages'] ?? 0)) {
            // Schedule the next chunk after a short delay
            as_schedule_single_action(
                time() + 5, 
                'wpwps_schedule_import_chunk',
                [$page + 1, $limit]
            );
        } else {
            // Schedule final cleanup
            as_schedule_single_action(
                time() + 60,
                'wpwps_import_complete', 
                []
            );
        }
        
        return true;
    }
    
    /**
     * Import a single product
     *
     * @param int $shop_id Shop ID
     * @param string $product_id Printify Product ID
     * @return int|WP_Error WC Product ID or error
     */
    public function importSingleProduct($shop_id, $product_id) {
        $this->logger->log("Importing product {$product_id} from shop {$shop_id}");
        
        // Get product details from Printify
        $product_data = $this->api->getProduct($shop_id, $product_id);
        
        if (is_wp_error($product_data)) {
            $this->logger->log("Error fetching product {$product_id}: " . $product_data->get_error_message());
            return $product_data;
        }
        
        // Check if product already exists in WooCommerce
        $wc_product_id = $this->getWooProductByPrintifyId($product_id);
        
        // Process product
        if ($wc_product_id) {
            return $this->updateProduct($wc_product_id, $product_data);
        } else {
            return $this->createProduct($product_data);
        }
    }
    
    /**
     * Get WooCommerce product by Printify product ID
     *
     * @param string $printify_id Printify product ID
     * @return int|false WooCommerce product ID or false if not found
     */
    private function getWooProductByPrintifyId($printify_id) {
        $args = [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'value' => $printify_id,
                    'compare' => '='
                ]
            ],
            'fields' => 'ids',
            'posts_per_page' => 1
        ];
        
        $products = get_posts($args);
        
        return !empty($products) ? $products[0] : false;
    }
    
    /**
     * Create a new WooCommerce product
     *
     * @param array $product_data Printify product data
     * @return int|WP_Error Product ID or error
     */
    private function createProduct($product_data) {
        // Create the product as variable product
        $product = new \WC_Product_Variable();
        
        // Set basic product data
        $product->set_name($product_data['title']);
        $product->set_description($product_data['description']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        
        // Save for ID
        $product->save();
        $product_id = $product->get_id();
        
        // Add printify metadata
        update_post_meta($product_id, '_printify_product_id', $product_data['id']);
        update_post_meta($product_id, '_print_provider', $product_data['print_provider']['id']);
        
        // Process categories
        $this->categoriesHelper->processCategories($product_id, $product_data);
        
        // Process tags
        $this->tagsHelper->processTags($product_id, $product_data);
        
        // Process images
        $this->imagesHelper->processImages($product_id, $product_data);
        
        // Process variants
        $this->variantsHelper->processVariants($product_id, $product_data);
        
        // Save again after all processing
        $product->save();
        
        $this->logger->log("Created new product ID: {$product_id} for Printify product: {$product_data['id']}");
        
        // Increment processed count
        $processed = (int)get_option('wpwps_import_processed_products', 0);
        update_option('wpwps_import_processed_products', $processed + 1);
        
        return $product_id;
    }
    
    /**
     * Update an existing WooCommerce product
     *
     * @param int $product_id WooCommerce product ID
     * @param array $product_data Printify product data
     * @return int|WP_Error Product ID or error
     */
    private function updateProduct($product_id, $product_data) {
        // Get existing product
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new \WP_Error('invalid_product', 'Product does not exist');
        }
        
        // Update basic product data
        $product->set_name($product_data['title']);
        $product->set_description($product_data['description']);
        
        // Update categories
        $this->categoriesHelper->processCategories($product_id, $product_data);
        
        // Update tags
        $this->tagsHelper->processTags($product_id, $product_data);
        
        // Update images
        $this->imagesHelper->processImages($product_id, $product_data);
        
        // Update variants
        $this->variantsHelper->processVariants($product_id, $product_data);
        
        // Save
        $product->save();
        
        $this->logger->log("Updated product ID: {$product_id} for Printify product: {$product_data['id']}");
        
        // Increment processed count
        $processed = (int)get_option('wpwps_import_processed_products', 0);
        update_option('wpwps_import_processed_products', $processed + 1);
        
        return $product_id;
    }
    
    /**
     * Get import progress 
     *
     * @return array Progress data
     */
    public function getImportProgress() {
        $total = (int)get_option('wpwps_import_total_products', 0);
        $processed = (int)get_option('wpwps_import_processed_products', 0);
        $in_progress = get_option('wpwps_import_in_progress', 'no');
        $started = (int)get_option('wpwps_import_started', 0);
        
        $percent = $total > 0 ? round(($processed / $total) * 100) : 0;
        
        return [
            'total' => $total,
            'processed' => $processed,
            'percent' => $percent,
            'in_progress' => $in_progress === 'yes',
            'started' => $started,
            'elapsed' => $started > 0 ? current_time('timestamp') - $started : 0,
        ];
    }
    
    /**
     * Clear all imported products
     *
     * @return int Number of products deleted
     */
    public function clearAllProducts() {
        $args = [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'compare' => 'EXISTS',
                ]
            ],
            'fields' => 'ids',
            'posts_per_page' => -1,
        ];
        
        $products = get_posts($args);
        $count = count($products);
        
        foreach ($products as $product_id) {
            wp_delete_post($product_id, true);
        }
        
        $this->logger->log("Deleted {$count} Printify products");
        
        return $count;
    }
}