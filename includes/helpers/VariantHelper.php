<?php
/**
 * Variant Helper class with HPOS-compatible approaches
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */
 
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class VariantHelper extends BaseHelper {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get variation by Printify variant ID using data abstraction
     *
     * @param int $product_id Product ID
     * @param string $printify_variant_id Printify variant ID
     * @return \WC_Product_Variation|false Variation object or false
     */
    public function getVariationByPrintifyVariantId($product_id, $printify_variant_id) {
        // Using WooCommerce's abstraction layer
        $variations = wc_get_products([
            'type' => 'variation',
            'parent' => $product_id,
            'meta_key' => '_printify_variant_id',
            'meta_value' => $printify_variant_id,
            'return' => 'objects',
            'limit' => 1
        ]);
        
        return !empty($variations) ? reset($variations) : false;
    }
    
    /**
     * Add product variations using data abstraction
     *
     * @param int $product_id Product ID
     * @param array $variations Variation data
     * @return array Created variation IDs
     */
    public function addProductVariations($product_id, $variations) {
        $created_ids = [];
        
        foreach ($variations as $variation_data) {
            $printify_variant_id = $variation_data['meta_data']['_printify_variant_id'] ?? '';
            
            // Check if variation exists
            $variation = $this->getVariationByPrintifyVariantId($product_id, $printify_variant_id);
            
            if ($variation) {
                // Update existing variation
                $this->updateVariation($variation, $variation_data);
                $created_ids[] = $variation->get_id();
            } else {
                // Create new variation
                $variation_id = $this->createVariation($product_id, $variation_data);
                if ($variation_id) {
                    $created_ids[] = $variation_id;
                }
            }
        }
        
        return $created_ids;
    }
    
    /**
     * Create new variation using data abstraction
     *
     * @param int $product_id Parent product ID
     * @param array $data Variation data
     * @return int|bool Variation ID or false on failure
     */
    public function createVariation($product_id, $data) {
        try {
            $variation = new \WC_Product_Variation();
            $variation->set_parent_id($product_id);
            
            // Set variation data - always use retail_price
            if (isset($data['regular_price'])) $variation->set_regular_price($data['regular_price']);
            if (isset($data['sale_price'])) $variation->set_sale_price($data['sale_price']);
            if (isset($data['sku'])) $variation->set_sku($data['sku']);
            
            // Set attributes
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                $attributes = [];
                
                foreach ($data['attributes'] as $attribute) {
                    $attributes['attribute_' . sanitize_title($attribute['name'])] = $attribute['option'];
                }
                
                $variation->set_attributes($attributes);
            }
            
            // Set meta data
            if (isset($data['meta_data']) && is_array($data['meta_data'])) {
                foreach ($data['meta_data'] as $key => $value) {
                    $variation->update_meta_data($key, $value);
                }
            }
            
            // Add audit trail
            $variation->update_meta_data('_printify_created_at', $this->timestamp);
            $variation->update_meta_data('_printify_created_by', $this->user);
            
            $variation_id = $variation->save();
            
            LogHelper::getInstance()->debug('Created variation', [
                'variation_id' => $variation_id,
                'product_id' => $product_id
            ]);
            
            return $variation_id;
            
        } catch (\Exception $e) {
            LogHelper::getInstance()->error('Error creating variation', [
                'error' => $e->getMessage(),
                'product_id' => $product_id
            ]);
            return false;
        }
    }
    
    /**
     * Update variation using data abstraction
     *
     * @param \WC_Product_Variation $variation Variation object
     * @param array $data Variation data
     * @return bool Success status
     */
    public function updateVariation($variation, $data) {
        try {
            // Set variation data - always use retail_price
            if (isset($data['regular_price'])) $variation->set_regular_price($data['regular_price']);
            if (isset($data['sale_price'])) $variation->set_sale_price($data['sale_price']);
            if (isset($data['sku'])) $variation->set_sku($data['sku']);
            
            // Set attributes
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                $attributes = [];
                
                foreach ($data['attributes'] as $attribute) {
                    $attributes['attribute_' . sanitize_title($attribute['name'])] = $attribute['option'];
                }
                
                $variation->set_attributes($attributes);
            }
            
            // Set meta data
            if (isset($data['meta_data']) && is_array($data['meta_data'])) {
                foreach ($data['meta_data'] as $key => $value) {
                    $variation->update_meta_data($key, $value);
                }
            }
            
            // Add audit trail
            $variation->update_meta_data('_printify_updated_at', $this->timestamp);
            $variation->update_meta_data('_printify_updated_by', $this->user);
            
            $variation->save();
            
            return true;
            
        } catch (\Exception $e) {
            LogHelper::getInstance()->error('Error updating variation', [
                'error' => $e->getMessage(),
                'variation_id' => $variation->get_id()
            ]);
            return false;
        }
    }
}