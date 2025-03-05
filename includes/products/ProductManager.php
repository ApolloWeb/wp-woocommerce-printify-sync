            // Add variant image if available
            if (isset($variant['image_url']) && !empty($variant['image_url'])) {
                $variation_create['image'] = array('src' => $variant['image_url']);
            } elseif (isset($printify_product['images'][0]['src'])) {
                $variation_create['image'] = array('src' => $printify_product['images'][0]['src']);
            }
            
            // Add attributes
            foreach ($printify_product['options'] as $option_index => $option) {
                $option_name = $option['name'];
                
                if (isset($variant['options'][$option_index])) {
                    $option_value = $variant['options'][$option_index];
                    
                    $variation_create['attributes'][] = array(
                        'name' => $option_name,
                        'option' => $option_value
                    );
                }
            }
            
            // Add Printify metadata
            $variation_create['meta_data'] = array(
                array(
                    'key' => '_printify_variant_id',
                    'value' => $variant['id']
                )
            );
            
            // Add production cost if available
            if (isset($variant['cost'])) {
                $variation_create['meta_data'][] = array(
                    'key' => '_printify_production_cost',
                    'value' => $variant['cost'] / 100 // Convert cents to dollars
                );
            }
            
            $to_create[] = $variation_create;
        }
        
        // Update existing variations in batch
        if (!empty($to_update)) {
            $response = $this->wc_api->request("products/{$wc_product_id}/variations/batch", array(
                'method' => 'POST',
                'body' => array(
                    'update' => $to_update
                )
            ));
            
            if (!$response['success']) {
                Logger::get_instance()->error('Failed to update product variations', array(
                    'wc_product_id' => $wc_product_id,
                    'error' => $response['message'],
                    'timestamp' => $this->timestamp
                ));
                return false;
            }
        }
        
        // Create new variations in batch
        if (!empty($to_create)) {
            $response = $this->wc_api->request("products/{$wc_product_id}/variations/batch", array(
                'method' => 'POST',
                'body' => array(
                    'create' => $to_create
                )
            ));
            
            if (!$response['success']) {
                Logger::get_instance()->error('Failed to create new product variations', array(
                    'wc_product_id' => $wc_product_id,
                    'error' => $response['message'],
                    'timestamp' => $this->timestamp
                ));
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get WooCommerce product by Printify ID
     *
     * @param string $printify_id Printify product ID
     * @return int|null WooCommerce product ID or null if not found
     */
    private function get_product_by_printify_id($printify_id) {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' AND meta_value = %s 
            LIMIT 1",
            $printify_id
        ));
        
        return $product_id ? (int) $product_id : null;
    }
    
    /**
     * Prepare product attributes from Printify options
     *
     * @param array $printify_product Printify product data
     * @return array WooCommerce attributes
     */
    private function prepare_product_attributes($printify_product) {
        $attributes = array();
        
        if (!isset($printify_product['options']) || empty($printify_product['options'])) {
            return $attributes;
        }
        
        foreach ($printify_product['options'] as $option) {
            if (!isset($option['name']) || !isset($option['values'])) {
                continue;
            }
            
            $attribute = array(
                'name' => $option['name'],
                'position' => 0,
                'visible' => true,
                'variation' => true,
                'options' => $option['values']
            );
            
            $attributes[] = $attribute;
        }
        
        return $attributes;
    }
    
    /**
     * Prepare product images from Printify images
     *
     * @param array $printify_images Printify images
     * @return array WooCommerce images
     */
    private function prepare_product_images($printify_images) {
        $images = array();
        
        foreach ($printify_images as $index => $image) {
            $images[] = array(
                'src' => $image['src'],
                'position' => $index
            );
        }
        
        return $images;
    }
    
    /**
     * Map Printify tags to WooCommerce categories
     *
     * @param array $tags Printify tags
     * @return array WooCommerce categories
     */
    private function map_categories($tags) {
        $categories = array();
        
        foreach ($tags as $tag) {
            $term = get_term_by('name', $tag, 'product_cat');
            
            if ($term) {
                $categories[] = array('id' => $term->term_id);
            } else {
                // Create new category
                $term = wp_insert_term($tag, 'product_cat');
                
                if (!is_wp_error($term)) {
                    $categories[] = array('id' => $term['term_id']);
                }
            }
        }
        
        return $categories;
    }
    
    /**
     * Generate short description from product data
     *
     * @param array $printify_product Printify product data
     * @return string Short description
     */
    private function generate_short_description($printify_product) {
        if (!empty($printify_product['description'])) {
            // Use first paragraph of description
            $paragraphs = explode("\n\n", $printify_product['description']);
            $short_desc = wp_trim_words($paragraphs[0], 30, '...');
            return $short_desc;
        }
        
        return '';
    }
    
    /**
     * Format price from Printify (cents to dollars)
     *
     * @param int|float $price Price in cents
     * @return string Formatted price
     */
    private function format_price($price) {
        return number_format($price / 100, 2);
    }
}