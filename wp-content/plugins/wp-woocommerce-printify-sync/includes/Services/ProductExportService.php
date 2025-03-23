<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;

/**
 * Product Export Service
 */
class ProductExportService {
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
        add_action('wp_ajax_wpwps_export_product', [$this, 'exportProductAjax']);
        add_action('save_post_product', [$this, 'handleProductSave'], 20, 3);
    }
    
    /**
     * Handle product save
     * 
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     * @param bool $update Is update
     */
    public function handleProductSave(int $post_id, \WP_Post $post, bool $update): void {
        // Skip auto-exports
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check if auto-export is enabled
        if (!$this->shouldAutoExport()) {
            return;
        }
        
        // Only export published products
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Schedule export
        as_schedule_single_action(
            time() + 5, 
            'wpwps_export_product', 
            [$post_id],
            'wp-woocommerce-printify-sync'
        );
    }
    
    /**
     * AJAX handler for manual product export
     */
    public function exportProductAjax(): void {
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
            $result = $this->exportProduct($product_id);
            
            if ($result['success']) {
                wp_send_json_success([
                    'message' => __('Product exported successfully to Printify', 'wp-woocommerce-printify-sync'),
                    'product_id' => $product_id,
                    'printify_id' => $result['printify_id']
                ]);
            } else {
                wp_send_json_error(['message' => $result['message']]);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Export WooCommerce product to Printify
     * 
     * @param int $product_id WooCommerce product ID
     * @return array Result with success status and details
     */
    public function exportProduct(int $product_id): array {
        $this->logger->log("Exporting product #{$product_id} to Printify", 'info');
        
        try {
            $product = wc_get_product($product_id);
            
            if (!$product) {
                throw new \Exception("Product #{$product_id} not found");
            }
            
            // Check if product already has a Printify ID
            $printify_id = get_post_meta($product_id, '_printify_product_id', true);
            
            // Prepare product data for Printify
            $printify_data = $this->prepareProductData($product);
            
            if ($printify_id) {
                // Update existing product
                $this->logger->log("Updating existing Printify product {$printify_id}", 'info');
                $response = $this->api->updateProduct($printify_id, $printify_data);
            } else {
                // Create new product
                $this->logger->log("Creating new Printify product for WooCommerce product #{$product_id}", 'info');
                $response = $this->api->createProduct($printify_data);
                
                // Store Printify ID
                update_post_meta($product_id, '_printify_product_id', $response['id']);
                update_post_meta($product_id, '_printify_exported', 'yes');
                update_post_meta($product_id, '_printify_export_date', current_time('mysql'));
            }
            
            $this->logger->log("Product #{$product_id} exported successfully to Printify as product {$response['id']}", 'info');
            
            return [
                'success' => true,
                'message' => "Product exported successfully",
                'printify_id' => $response['id']
            ];
            
        } catch (\Exception $e) {
            $this->logger->log("Error exporting product #{$product_id}: " . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Prepare product data for Printify
     * 
     * @param \WC_Product $product WooCommerce product
     * @return array Prepared product data
     */
    private function prepareProductData(\WC_Product $product): array {
        $product_data = [
            'title' => $product->get_name(),
            'description' => $product->get_description(),
            'tags' => $this->getProductTags($product),
            'print_provider_id' => $this->getDefaultPrintProvider(),
            'blueprint_id' => $this->getDefaultBlueprint(),
            'print_areas' => $this->getPrintAreas($product),
            'variants' => $this->getProductVariants($product)
        ];
        
        // Add options if this is a variable product
        if ($product->is_type('variable')) {
            $product_data['options'] = $this->getProductOptions($product);
        }
        
        return $product_data;
    }
    
    /**
     * Get product tags
     * 
     * @param \WC_Product $product WooCommerce product
     * @return array Product tags
     */
    private function getProductTags(\WC_Product $product): array {
        $terms = wp_get_post_terms($product->get_id(), 'product_tag');
        
        if (is_wp_error($terms)) {
            return [];
        }
        
        return array_map(function($term) {
            return $term->name;
        }, $terms);
    }
    
    /**
     * Get print areas configuration
     * 
     * @param \WC_Product $product WooCommerce product
     * @return array Print areas
     */
    private function getPrintAreas(\WC_Product $product): array {
        // Default to product featured image for front print area
        $print_areas = [];
        
        $image_id = $product->get_image_id();
        if ($image_id) {
            $image_url = wp_get_attachment_url($image_id);
            
            if ($image_url) {
                $print_areas['front'] = [
                    'artwork_id' => null,
                    'placement' => 'center',
                    'images' => [
                        [
                            'id' => null,
                            'x' => 0.5,
                            'y' => 0.5,
                            'scale' => 1,
                            'angle' => 0,
                            'url' => $image_url
                        ]
                    ]
                ];
            }
        }
        
        return $print_areas;
    }
    
    /**
     * Get product variants
     * 
     * @param \WC_Product $product WooCommerce product
     * @return array Product variants
     */
    private function getProductVariants(\WC_Product $product): array {
        $variants = [];
        
        // Default variant for simple products
        if ($product->is_type('simple')) {
            $variants[] = [
                'id' => null,
                'price' => floor($product->get_price() * 100), // Printify uses cents
                'is_enabled' => $product->get_status() === 'publish'
            ];
        } 
        // Variants for variable products
        else if ($product->is_type('variable')) {
            $variations = $product->get_available_variations();
            
            foreach ($variations as $variation) {
                $variation_obj = wc_get_product($variation['variation_id']);
                
                $variants[] = [
                    'id' => null,
                    'price' => floor($variation_obj->get_price() * 100),
                    'is_enabled' => $variation_obj->get_status() === 'publish',
                    'sku' => $variation_obj->get_sku(),
                    'options' => $this->getVariationOptions($variation_obj)
                ];
            }
        }
        
        return $variants;
    }
    
    /**
     * Get variation options
     * 
     * @param \WC_Product_Variation $variation WooCommerce variation
     * @return array Variation options
     */
    private function getVariationOptions(\WC_Product_Variation $variation): array {
        $options = [];
        $attributes = $variation->get_attributes();
        
        foreach ($attributes as $attr_name => $attr_value) {
            $options[] = $attr_value;
        }
        
        return $options;
    }
    
    /**
     * Get product options (for variable products)
     * 
     * @param \WC_Product $product WooCommerce product
     * @return array Product options
     */
    private function getProductOptions(\WC_Product $product): array {
        $options = [];
        $attributes = $product->get_attributes();
        
        foreach ($attributes as $attribute) {
            if ($attribute->is_taxonomy()) {
                $terms = wp_get_post_terms($product->get_id(), $attribute->get_name());
                
                if (!is_wp_error($terms)) {
                    $values = array_map(function($term) {
                        return $term->name;
                    }, $terms);
                    
                    $options[] = [
                        'name' => wc_attribute_label($attribute->get_name()),
                        'type' => 'dropdown',
                        'values' => $values
                    ];
                }
            } else {
                $values = $attribute->get_options();
                
                $options[] = [
                    'name' => $attribute->get_name(),
                    'type' => 'dropdown',
                    'values' => $values
                ];
            }
        }
        
        return $options;
    }
    
    /**
     * Get default print provider ID
     * 
     * @return int Print provider ID
     */
    private function getDefaultPrintProvider(): int {
        return (int)get_option('wpwps_default_print_provider_id', 1);
    }
    
    /**
     * Get default blueprint ID
     * 
     * @return int Blueprint ID
     */
    private function getDefaultBlueprint(): int {
        return (int)get_option('wpwps_default_blueprint_id', 1);
    }
    
    /**
     * Check if auto-export is enabled
     * 
     * @return bool Auto-export enabled
     */
    private function shouldAutoExport(): bool {
        return get_option('wpwps_auto_export_products', 'no') === 'yes';
    }
}
