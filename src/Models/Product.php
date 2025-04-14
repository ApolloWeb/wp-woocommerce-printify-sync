<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Models;

use ApolloWeb\WPWooCommercePrintifySync\Repositories\Interfaces\PrintifyMappingInterface;
use ApolloWeb\WPWooCommercePrintifySync\Storage\Interfaces\ProductDataInterface;

class Product {
    private $data;
    private $product_id;
    private $mapping_repository;
    private $storage;
    
    public function __construct(
        array $printify_data, 
        PrintifyMappingInterface $mapping_repository,
        ProductDataInterface $storage
    ) {
        $this->data = $printify_data;
        $this->mapping_repository = $mapping_repository;
        $this->storage = $storage;
    }
    
    public function import() {
        try {
            // Create or update product
            $this->product_id = $this->createOrUpdateProduct();
            
            // Map attributes and variations
            $this->mapAttributes();
            $this->createVariations();
            
            // Set categories and tags
            $this->setCategories();
            $this->setTags();
            
            // Import images
            $this->importImages();
            
            // Save Printify meta
            $this->savePrintifyMeta();
            
            return $this->product_id;
            
        } catch (\Exception $e) {
            error_log('Product import error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function createOrUpdateProduct() {
        $args = [
            'post_title' => $this->data['title'],
            'post_content' => $this->data['description'],
            'post_status' => 'publish',
            'post_type' => 'product'
        ];
        
        // Check if product exists
        $existing_id = $this->getExistingProduct();
        if ($existing_id) {
            $args['ID'] = $existing_id;
            return wp_update_post($args);
        }
        
        return wp_insert_post($args);
    }
    
    private function getExistingProduct() {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' 
            AND meta_value = %s",
            $this->data['id']
        ));
    }
    
    private function mapAttributes() {
        $attributes = [];
        
        foreach ($this->data['options'] as $option) {
            $attr_name = wc_sanitize_taxonomy_name($option['name']);
            $attr_label = $option['name'];
            
            // Create attribute
            $attribute = $this->createProductAttribute($attr_name, $attr_label);
            
            // Get all values
            $values = wp_list_pluck($option['values'], 'title');
            
            $attributes[$attr_name] = [
                'name' => $attr_name,
                'value' => $values,
                'visible' => true,
                'variation' => true
            ];
        }
        
        update_post_meta($this->product_id, '_product_attributes', $attributes);
    }
    
    private function createVariations() {
        // Delete existing variations
        $this->deleteExistingVariations();
        
        foreach ($this->data['variants'] as $variant) {
            $variation_id = $this->createVariation($variant);
            if ($variation_id) {
                $this->updateVariationMeta($variation_id, $variant);
            }
        }
    }
    
    private function importImages() {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $image_ids = [];
        foreach ($this->data['images'] as $index => $image) {
            $image_id = $this->importSingleImage($image['src'], $index === 0);
            if ($image_id) {
                $image_ids[] = $image_id;
            }
        }
        
        // First image is always featured image
        if (!empty($image_ids)) {
            set_post_thumbnail($this->product_id, $image_ids[0]);
            
            // Add remaining images to gallery
            if (count($image_ids) > 1) {
                $gallery_ids = array_slice($image_ids, 1);
                update_post_meta($this->product_id, '_product_image_gallery', implode(',', $gallery_ids));
            }
            
            // Force SMUSH processing if available
            if (class_exists('\WP_Smush')) {
                global $WP_Smush;
                foreach ($image_ids as $img_id) {
                    // Smush the image
                    $WP_Smush->core()->mod->smush->process_smush($img_id);
                    
                    // If CDN enabled, ensure image is updated there
                    if ($WP_Smush->core()->mod->cdn && $WP_Smush->core()->mod->cdn->is_active()) {
                        $WP_Smush->core()->mod->cdn->update_image($img_id);
                    }
                }
            }
        }
    }

    private function savePrintifyMeta() {
        $variant_ids = array_column($this->data['variants'], 'id');
        $this->mapping_repository->savePrintifyIds(
            $this->product_id,
            $this->data['id'],
            $variant_ids
        );
        
        update_post_meta($this->product_id, '_printify_provider_id', $this->data['provider_id']);
        update_post_meta($this->product_id, '_printify_last_synced', current_time('mysql'));
    }

    private function setCategories() {
        if (!isset($this->data['product_type'])) {
            return;
        }

        // Split category path (e.g. "Apparel/T-Shirts" -> ["Apparel", "T-Shirts"])
        $categories = explode('/', $this->data['product_type']);
        $parent_id = 0;
        $category_ids = [];

        foreach ($categories as $category_name) {
            $category_name = trim($category_name);
            $slug = sanitize_title($category_name);
            
            // Check if category exists
            $term = get_term_by('slug', $slug, 'product_cat');
            
            if (!$term) {
                // Create new category
                $term = wp_insert_term($category_name, 'product_cat', [
                    'slug' => $slug,
                    'parent' => $parent_id
                ]);
                
                if (!is_wp_error($term)) {
                    $parent_id = $term['term_id'];
                    $category_ids[] = $term['term_id'];
                }
            } else {
                $parent_id = $term->term_id;
                $category_ids[] = $term->term_id;
            }
        }

        if (!empty($category_ids)) {
            wp_set_object_terms($this->product_id, $category_ids, 'product_cat');
        }
    }

    private function setTags() {
        if (empty($this->data['tags'])) {
            return;
        }

        $tags = array_map('trim', $this->data['tags']);
        wp_set_object_terms($this->product_id, $tags, 'product_tag');
    }

    private function createVariation($variant) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($this->product_id);
        
        // Set basic variation data
        $variation->set_status('publish');
        $variation->set_sku($variant['sku']);
        
        // Set price in GBP
        $price = $this->convertToGBP($variant['price']);
        $variation->set_regular_price($price);
        $variation->set_price($price);
        
        // Set cost price in meta
        $cost = $this->convertToGBP($variant['cost']);
        
        // Set attributes
        $attributes = [];
        foreach ($variant['options'] as $key => $value) {
            $attr_name = wc_sanitize_taxonomy_name($key);
            $attributes["attribute_" . $attr_name] = sanitize_title($value);
        }
        $variation->set_attributes($attributes);
        
        // Set stock status based on availability
        $is_enabled = $variant['is_enabled'] ?? true;
        $is_available = $variant['available'] ?? true;
        
        if ($is_enabled && $is_available) {
            $variation->set_stock_status('instock');
            $variation->set_stock_quantity(999); // Set high stock for print-on-demand
        } else {
            $variation->set_stock_status('outofstock');
            $variation->set_stock_quantity(0);
        }
        
        $variation_id = $variation->save();
        
        if ($variation_id) {
            update_post_meta($variation_id, '_printify_cost_price', $cost);
            update_post_meta($variation_id, '_printify_variant_id', $variant['id']);
        }
        
        return $variation_id;
    }

    private function updateVariationMeta($variation_id, $variant) {
        // Set additional meta data for variations
        update_post_meta($variation_id, '_thumbnail_id', $this->getVariantImage($variant));
        update_post_meta($variation_id, '_virtual', 'no');
        update_post_meta($variation_id, '_manage_stock', 'yes');
        update_post_meta($variation_id, '_backorders', 'no');
    }

    private function getVariantImage($variant) {
        if (!empty($variant['image']['src'])) {
            $image_id = $this->importSingleImage($variant['image']['src']);
            if ($image_id) {
                return $image_id;
            }
        }
        return '';
    }

    private function importSingleImage($image_url, $is_featured = false) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Check if image already exists
        $existing_id = $this->getExistingImageId($image_url);
        if ($existing_id) {
            return $existing_id;
        }

        // Download and import image
        $image_id = media_sideload_image($image_url, $this->product_id, '', 'id');
        if (!is_wp_error($image_id)) {
            // Store original URL and featured status
            update_post_meta($image_id, '_printify_original_url', $image_url);
            if ($is_featured) {
                update_post_meta($image_id, '_printify_featured_image', '1');
            }
            
            // Update image alt text
            $product_title = get_the_title($this->product_id);
            $alt_text = $is_featured ? 
                sprintf('%s - Main Product Image', $product_title) :
                sprintf('%s - Product Gallery Image', $product_title);
            update_post_meta($image_id, '_wp_attachment_image_alt', $alt_text);
            
            return $image_id;
        }

        return false;
    }

    private function getExistingImageId($image_url) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_original_url' 
            AND meta_value = %s",
            $image_url
        ));
    }

    private function deleteExistingVariations() {
        $variations = $this->storage->getPostsByType('product_variation', [
            'post_parent' => $this->product_id,
            'fields' => 'ids'
        ]);

        foreach ($variations as $variation_id) {
            $this->storage->deleteProduct($variation_id);
        }
    }

    private function convertToGBP($amount) {
        // Add currency conversion if needed
        // For now, assuming prices are already in GBP
        return floatval($amount);
    }

    private function createProductAttribute($name, $label) {
        $attribute_id = wc_attribute_taxonomy_id_by_name($name);
        
        if (!$attribute_id) {
            wc_create_attribute([
                'name' => $label,
                'slug' => $name,
                'type' => 'select',
                'order_by' => 'menu_order',
                'has_archives' => false,
            ]);
            
            // Register the taxonomy
            register_taxonomy(
                'pa_' . $name,
                ['product'],
                ['hierarchical' => false]
            );
        }
        
        return 'pa_' . $name;
    }
}
