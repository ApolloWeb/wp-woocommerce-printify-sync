<?php
/**
 * Product Importer - Uses WooCommerce REST API for product creation/updates
 * with enhanced metadata support for Printify
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Products
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Products;

use ApolloWeb\WPWooCommercePrintifySync\API\WooCommerce\WooCommerceApiClient;
use ApolloWeb\WPWooCommercePrintifySync\API\Printify\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

class ProductImporter {
    private $wc_api;
    private $printify_api;
    private $timestamp;
    private $user;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->timestamp = '2025-03-05 19:37:13';
        $this->user = 'ApolloWeb';
        $this->wc_api = WooCommerceApiClient::getInstance();
        $this->printify_api = PrintifyApiClient::getInstance();
    }
    
    /**
     * Import product from Printify to WooCommerce
     *
     * @param array $printify_product Printify product data
     * @param int $shop_id Printify shop ID
     * @return int|bool WooCommerce product ID or false on failure
     */
    public function importProduct(array $printify_product, int $shop_id) {
        // Prepare product data
        $product_data = $this->prepareProductData($printify_product);
        
        // Create or update product via WC API
        $existing_product_id = $this->getProductByPrintifyId($printify_product['id']);
        
        if ($existing_product_id) {
            // Update existing product
            $product_data['id'] = $existing_product_id;
            $response = $this->wc_api->request("products/{$existing_product_id}", [
                'method' => 'PUT',
                'body' => $product_data
            ]);
            
            $action = 'updated';
        } else {
            // Create new product
            $response = $this->wc_api->request('products', [
                'method' => 'POST',
                'body' => $product_data
            ]);
            
            $action = 'created';
        }
        
        if ($response['success'] && isset($response['body']['id'])) {
            $product_id = $response['body']['id'];
            
            // Store all Printify-specific metadata
            $this->storeProductPrintifyMetadata($product_id, $printify_product, $shop_id);
            
            // If this is a variable product, store variant-specific metadata
            if (isset($response['body']['type']) && $response['body']['type'] === 'variable') {
                $this->storeVariationPrintifyMetadata($product_id, $printify_product);
            }
            
            Logger::getInstance()->info("Product {$action} successfully", [
                'printify_id' => $printify_product['id'],
                'product_id' => $product_id,
                'timestamp' => $this->timestamp,
                'user' => $this->user
            ]);
            
            return $product_id;
        } else {
            Logger::getInstance()->error("Failed to {$action} product", [
                'printify_id' => $printify_product['id'],
                'error' => $response['message'] ?? 'Unknown error',
                'timestamp' => $this->timestamp
            ]);
            
            return false;
        }
    }
    
    /**
     * Get WooCommerce product ID by Printify ID
     *
     * @param string $printify_id Printify product ID
     * @return int|null WooCommerce product ID or null if not found
     */
    private function getProductByPrintifyId(string $printify_id) {
        $response = $this->wc_api->request('products', [
            'meta_key' => '_printify_product_id',
            'meta_value' => $printify_id
        ]);
        
        if ($response['success'] && !empty($response['body'])) {
            return $response['body'][0]['id'] ?? null;
        }
        
        return null;
    }
    
    /**
     * Store Printify-specific metadata for a product
     *
     * @param int $product_id WooCommerce product ID
     * @param array $printify_product Printify product data
     * @param int $shop_id Printify shop ID
     * @return bool Success status
     */
    private function storeProductPrintifyMetadata(int $product_id, array $printify_product, int $shop_id): bool {
        // Build metadata array
        $meta_data = [
            '_printify_product_id' => $printify_product['id'],
            '_printify_shop_id' => $shop_id,
            '_printify_synced_at' => $this->timestamp,
            '_printify_synced_by' => $this->user
        ];
        
        // Add provider data if available
        if (isset($printify_product['print_provider']['id'])) {
            $meta_data['_printify_provider_id'] = $printify_product['print_provider']['id'];
            
            if (isset($printify_product['print_provider']['title'])) {
                $meta_data['_printify_provider_name'] = $printify_product['print_provider']['title'];
            }
        }
        
        // Add blueprint data if available
        if (isset($printify_product['blueprint_id'])) {
            $meta_data['_printify_blueprint_id'] = $printify_product['blueprint_id'];
        }
        
        // Add print areas if available
        if (isset($printify_product['print_areas'])) {
            $meta_data['_printify_print_areas'] = json_encode($printify_product['print_areas']);
        }
        
        // Add shipping profiles if available
        if (isset($printify_product['shipping_profiles'])) {
            $meta_data['_printify_shipping_profiles'] = json_encode($printify_product['shipping_profiles']);
        }
        
        // Add printing details if available
        if (isset($printify_product['print_details'])) {
            $meta_data['_printify_print_details'] = json_encode($printify_product['print_details']);
        }
        
        // Add external IDs if available
        if (isset($printify_product['external_id'])) {
            $meta_data['_printify_external_id'] = $printify_product['external_id'];
        }
        
        // Store product costs for reporting
        if (isset($printify_product['variants'])) {
            $production_costs = [];
            $profit_margins = [];
            
            foreach ($printify_product['variants'] as $variant) {
                if (isset($variant['cost']) && isset($variant['price'])) {
                    $variant_id = $variant['id'];
                    $cost = $variant['cost'] / 100; // Convert cents to dollars
                    $price = $variant['price'] / 100; // Convert cents to dollars
                    $margin = $price - $cost;
                    $margin_percent = ($price > 0) ? ($margin / $price) * 100 : 0;
                    
                    $production_costs[$variant_id] = $cost;
                    $profit_margins[$variant_id] = [
                        'cost' => $cost,
                        'price' => $price,
                        'margin' => $margin,
                        'margin_percent' => round($margin_percent, 2)
                    ];
                }
            }
            
            if (!empty($production_costs)) {
                $meta_data['_printify_production_costs'] = json_encode($production_costs);
                $meta_data['_printify_profit_margins'] = json_encode($profit_margins);
            }
        }
        
        // Store metadata in bulk
        return $this->wc_api->storeProductMetaData($product_id, $meta_data);
    }
    
    /**
     * Store Printify-specific metadata for product variations
     *
     * @param int $product_id WooCommerce product ID
     * @param array $printify_product Printify product data
     * @return bool Success status
     */
    private function storeVariationPrintifyMetadata(int $product_id, array $printify_product): bool {
        if (!isset($printify_product['variants']) || empty($printify_product['variants'])) {
            return false;
        }
        
        // Get existing variations
        $response = $this->wc_api->request("products/{$product_id}/variations");
        
        if (!$response['success'] || empty($response['body'])) {
            Logger::getInstance()->error('Failed to retrieve product variations', [
                'product_id' => $product_id,
                'error' => $response['message'] ?? 'Unknown error',
                'timestamp' => $this->timestamp
            ]);
            return false;
        }
        
        $wc_variations = $response['body'];
        
        // For each Printify variant, find corresponding WC variation and update metadata
        foreach ($printify_product['variants'] as $printify_variant) {
            $variant_id = $printify_variant['id'];
            $variant_sku = $printify_variant['sku'] ?? '';
            $variant_options = $printify_variant['options'] ?? [];
            
            // Try to match with WC variation
            $matched_variation = null;
            
            foreach ($wc_variations as $wc_variation) {
                // Match by SKU if available
                if (!empty($variant_sku) && $variant_sku === ($wc_variation['sku'] ?? '')) {
                    $matched_variation = $wc_variation;
                    break;
                }
                
                // Match by attributes if no SKU match
                if (empty($matched_variation) && !empty($variant_options) && isset($wc_variation['attributes'])) {
                    $matches = true;
                    $wc_attributes = $wc_variation['attributes'];
                    
                    // For simplicity, we're assuming the attributes are in the same order
                    // In a production environment, you'd need more sophisticated matching
                    foreach ($wc_attributes as $index => $wc_attribute) {
                        if (!isset($variant_options[$index]) || $variant_options[$index] !== $wc_attribute['option']) {
                            $matches = false;
                            break;
                        }
                    }
                    
                    if ($matches) {
                        $matched_variation = $wc_variation;
                        break;
                    }
                }
            }
            
            if ($matched_variation) {
                $variation_id = $matched_variation['id'];
                
                // Build variation metadata
                $variation_meta = [
                    '_printify_variant_id' => $variant_id
                ];
                
                // Add cost data if available
                if (isset($printify_variant['cost'])) {
                    $variation_meta['_printify_production_cost'] = $printify_variant['cost'] / 100;
                }
                
                // Add profit margin if we can calculate it
                if (isset($printify_variant['cost']) && isset($printify_variant['price'])) {
                    $cost = $printify_variant['cost'] / 100;
                    $price = $printify_variant['price'] / 100;
                    $margin = $price - $cost;
                    $margin_percent = ($price > 0) ? ($margin / $price) * 100 : 0;
                    
                    $variation_meta['_printify_profit_margin'] = $margin;
                    $variation_meta['_printify_profit_margin_percent'] = round($margin_percent, 2);
                }
                
                // Store metadata for this variation
                $this->wc_api->storeProductMetaData($variation_id, $variation_meta);
            }
        }
        
        return true;
    }
    
    /**
     * Prepare product data for WooCommerce API
     *
     * @param array $printify_product Printify product data
     * @return array WooCommerce product data
     */
    private function prepareProductData(array $printify_product): array {
        // Basic product data
        $product_data = [
            'name' => $printify_product['title'],
            'status' => 'publish',
            'catalog_visibility' => 'visible'
        ];
        
        // Add description if available
        if (isset($printify_product['description'])) {
            $product_data['description'] = $printify_product['description'];
            $product_data['short_description'] = substr($printify_product['description'], 0, 150) . '...';
        }
        
        // Add price
        if (isset($printify_product['variants'][0]['price'])) {
            $product_data['regular_price'] = (string)($printify_product['variants'][0]['price'] / 100); // Printify prices are in cents
        }
        
        // Set as non-managed stock
        $product_data['manage_stock'] = false;
        
        // Add categories
        if (isset($printify_product['tags']) && !empty($printify_product['tags'])) {
            $product_data['categories'] = $this->mapCategories($printify_product['tags']);
        }
        
        // Add images
        if (isset($printify_product['images']) && !empty($printify_product['images'])) {
            $product_data['images'] = $this->prepareImages($printify_product['images']);
        }
        
        // Handle variants
        if (isset($printify_product['variants']) && count($printify_product['variants']) > 1) {
            $product_data['type'] = 'variable';
            $product_data['attributes'] = $this->prepareAttributes($printify_product);
        } else {
            $product_data['type'] = 'simple';
        }
        
        return $product_data;
    }
    
    /**
     * Map Printify tags to WooCommerce categories
     *
     * @param array $tags Printify tags
     * @return array Category IDs
     */
    private function mapCategories(array $tags): array {
        $categories = [];
        
        foreach ($tags as $tag) {
            // Find or create category
            $response = $this->wc_api->request('products/categories', [
                'search' => $tag
            ]);
            
            if ($response['success'] && !empty($response['body'])) {
                $categories[] = ['id' => $response['body'][0]['id']];
            } else {
                // Create new category
                $create_response = $this->wc_api->request('products/categories', [
                    'method' => 'POST',
                    'body' => [
                        'name' => $tag
                    ]
                ]);
                
                if ($create_response['success'] && isset($create_response['body']['id'])) {
                    $categories[] = ['id' => $create_response['body']['id']];
                }
            }
        }
        
        return $categories;
    }
    
    /**
     * Prepare product images for WooCommerce API
     *
     * @param array $images Printify images
     * @return array WooCommerce image data
     */
    private function prepareImages(array $images): array {
        $wc_images = [];
        
        foreach ($images as $index => $image) {
            $wc_images[] = [
                'src' => $image['src'],
                'position' => $index
            ];
        }
        
        return $wc_images;
    }
    
    /**
     * Prepare product attributes for WooCommerce API
     *
     * @param array $printify_product Printify product data
     * @return array WooCommerce attributes
     */
    private function prepareAttributes(array $printify_product): array {
        $attributes = [];
        
        if (!isset($printify_product['options']) || empty($printify_product['options'])) {
            return $attributes;
        }
        
        foreach ($printify_product['options'] as $option) {
            if (!isset($option['name']) || !isset($option['values'])) {
                continue;
            }
            
            $attribute = [
                'name' => $option['name'],
                'position' => 0,
                'visible' => true,
                'variation' => true,
                'options' => $option['values']
            ];
            
            $attributes[] = $attribute;
        }
        
        return $attributes;
    }
    
    /**
     * Create variations for a variable product
     *
     * @param int $product_id WooCommerce product ID
     * @param array $printify_product Printify product data
     * @return bool Success status
     */
    public function createVariations(int $product_id, array $printify_product): bool {
        if (!isset($printify_product['variants']) || !isset($printify_product['options'])) {
            return false;
        }
        
        $variations = [];
        
        foreach ($printify_product['variants'] as $variant) {
            $variation = [
                'regular_price' => (string)($variant['price'] / 100),
                'visible' => true,
                'manage_stock' => false,
                'attributes' => []
            ];
            
            // Add variant SKU if available
            if (isset($variant['sku']) && !empty($variant['sku'])) {
                $variation['sku'] = $variant['sku'];
            }
            
            // Add attributes
            foreach ($printify_product['options'] as $option_index => $option) {
                $option_name = $option['name'];
                
                if (isset($variant['options'][$option_index])) {
                    $option_value = $variant['options'][$option_index];
                    
                    $variation['attributes'][] = [
                        'name' => $option_name,
                        'option' => $option_value
                    ];
                }
            }
            
            $variations[] = $variation;
        }
        
        // Create variations in batch
        $response = $this->wc_api->request("products/{$product_id}/variations/batch", [
            'method' => 'POST',
            'body' => [
                'create' => $variations
            ]
        ]);
        
        if (!$response['success']) {
            Logger::getInstance()->error('Failed to create product variations', [
                'product_id' => $product_id,
                'error' => $response['message'] ?? 'Unknown error',
                'timestamp' => $this->timestamp
            ]);
            return false;
        }
        
        return true;
    }
}