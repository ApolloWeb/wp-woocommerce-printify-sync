<?php
/**
 * Product Helper class with HPOS-compatible approaches
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */
 
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class ProductHelper extends BaseHelper {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get product by Printify ID using data abstraction
     *
     * @param string $printify_id Printify product ID
     * @return \WC_Product|false Product object or false
     */
    public function getProductByPrintifyId($printify_id) {
        $products = wc_get_products([
            'limit' => 1,
            'meta_key' => '_printify_product_id',
            'meta_value' => $printify_id,
            'return' => 'objects',
        ]);
        
        return !empty($products) ? reset($products) : false;
    }
    
    /**
     * Get product ID by Printify ID using data abstraction
     *
     * @param string $printify_id Printify product ID
     * @return int|false Product ID or false
     */
    public function getProductIdByPrintifyId($printify_id) {
        $products = wc_get_products([
            'limit' => 1,
            'meta_key' => '_printify_product_id',
            'meta_value' => $printify_id,
            'return' => 'ids',
        ]);
        
        return !empty($products) ? (int)reset($products) : false;
    }
    
    /**
     * Import single product with HPOS compatibility
     *
     * @param int $shop_id Shop ID
     * @param string $printify_id Printify product ID
     * @return int|bool WooCommerce product ID or false on failure
     */
    public function importSingleProduct($shop_id, $printify_id) {
        // Get product details from Printify API
        $response = ApiHelper::getInstance()->sendPrintifyRequest("shops/{$shop_id}/products/{$printify_id}.json");
        
        if (!$response['success']) {
            LogHelper::getInstance()->error('Failed to get product details from Printify', [
                'printify_id' => $printify_id,
                'error' => $response['message']
            ]);
            return false;
        }
        
        $printify_product = $response['body'];
        
        // Check if product already exists
        $product = $this->getProductByPrintifyId($printify_id);
        
        // Convert Printify product to WooCommerce product
        $wc_product_data = $this->convertPrintifyToWooCommerceProduct($printify_product);
        
        if ($product) {
            // Update existing product
            $product_id = $product->get_id();
            $this->updateProduct($product, $wc_product_data);
        } else {
            // Create new product
            $product_id = $this->createProduct($wc_product_data);
        }
        
        if ($product_id) {
            // Get fresh product object
            $product = wc_get_product($product_id);
            
            // Store Printify data using data store abstraction
            $product->update_meta_data('_printify_product_id', $printify_id);
            $product->update_meta_data('_printify_shop_id', $shop_id);
            $product->update_meta_data('_printify_last_updated', $this->timestamp);
            $product->update_meta_data('_printify_updated_by', $this->user);
            $product->save();
            
            // Schedule image import
            ImageHelper::getInstance()->scheduleImageImport($product_id, $printify_product);
            
            return $product_id;
        }
        
        return false;
    }
    
    /**
     * Create product using WooCommerce abstraction
     * 
     * @param array $data Product data
     * @return int|bool Product ID or false on failure
     */
    private function createProduct($data) {
        try {
            // Create appropriate product object
            if ($data['type'] === 'simple') {
                $product = new \WC_Product_Simple();
            } else {
                $product = new \WC_Product_Variable();
            }
            
            // Set product data
            $this->updateProduct($product, $data);
            
            // Add variations if variable product
            if ($product->is_type('variable') && !empty($data['variations'])) {
                VariantHelper::getInstance()->addProductVariations($product->get_id(), $data['variations']);
            }
            
            return $product->get_id();
            
        } catch (\Exception $e) {
            LogHelper::getInstance()->error('Error creating product', [
                'error' => $e->getMessage(),
                'name' => $data['name'] ?? 'Unknown'
            ]);
            return false;
        }
    }
    
    /**
     * Update product using WooCommerce abstraction
     *
     * @param \WC_Product $product Product object
     * @param array $data Product data
     * @return bool Success status
     */
    private function updateProduct($product, $data) {
        try {
            // Set basic product data
            if (isset($data['name'])) $product->set_name($data['name']);
            if (isset($data['status'])) $product->set_status($data['status']);
            if (isset($data['description'])) $product->set_description($data['description']);
            if (isset($data['short_description'])) $product->set_short_description($data['short_description']);
            
            // Set price data (always use retail_price)
            if ($product->is_type('simple')) {
                if (isset($data['regular_price'])) $product->set_regular_price($data['regular_price']);
                if (isset($data['sale_price'])) $product->set_sale_price($data['sale_price']);
                if (isset($data['sku'])) $product->set_sku($data['sku']);
            }
            
            // Set attributes for variable products
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                $attributes = [];
                
                foreach ($data['attributes'] as $attribute) {
                    $attr = new \WC_Product_Attribute();
                    $attr->set_name($attribute['name']);
                    $attr->set_options($attribute['options']);
                    $attr->set_position($attribute['position'] ?? 0);
                    $attr->set_visible($attribute['visible'] ?? true);
                    $attr->set_variation($attribute['variation'] ?? true);
                    
                    $attributes[] = $attr;
                }
                
                $product->set_attributes($attributes);
            }
            
            // Set categories and tags
            if (isset($data['categories'])) $product->set_category_ids($data['categories']);
            if (isset($data['tags'])) $product->set_tag_ids($data['tags']);
            
            // Set meta data
            if (isset($data['meta_data']) && is_array($data['meta_data'])) {
                foreach ($data['meta_data'] as $key => $value) {
                    $product->update_meta_data($key, $value);
                }
            }
            
            // Add audit trail
            $product->update_meta_data('_printify_updated_at', $this->timestamp);
            $product->update_meta_data('_printify_updated_by', $this->user);
            
            // Save the product
            $product->save();
            
            return true;
            
        } catch (\Exception $e) {
            LogHelper::getInstance()->error('Error updating product', [
                'error' => $e->getMessage(),
                'product_id' => $product->get_id()
            ]);
            return false;
        }
    }
}