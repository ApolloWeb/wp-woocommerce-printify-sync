<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class ProductHelper {
    public static function processTerms(int $product_id, array $categories, array $tags) {
        // Process categories
        if (!empty($categories)) {
            $category_ids = array_map(function($category) {
                return self::getOrCreateTerm($category, 'product_cat');
            }, $categories);
            wp_set_object_terms($product_id, $category_ids, 'product_cat');
        }

        // Process tags
        if (!empty($tags)) {
            $tag_ids = array_map(function($tag) {
                return self::getOrCreateTerm($tag, 'product_tag');
            }, $tags);
            wp_set_object_terms($product_id, $tag_ids, 'product_tag');
        }
    }

    public static function processVariants(int $product_id, array $variants) {
        $product = wc_get_product($product_id);
        
        // Create attributes from variants
        $attributes = self::createAttributesFromVariants($variants);
        $product->set_attributes($attributes);
        
        // Create variations
        foreach ($variants as $variant) {
            $variation = new \WC_Product_Variation();
            $variation->set_parent_id($product_id);
            $variation->set_regular_price($variant['retail_price']);
            $variation->set_status('publish');
            
            // Set attributes
            foreach ($variant['attributes'] as $name => $value) {
                $variation->set_attribute($name, $value);
            }
            
            $variation->save();
        }
        
        $product->save();
    }

    public static function processImages(int $product_id, array $images) {
        foreach ($images as $index => $image_url) {
            as_enqueue_async_action(
                'wpwps_process_single_image',
                [
                    'product_id' => $product_id,
                    'image_url' => $image_url,
                    'is_featured' => ($index === 0)
                ],
                'product-images'
            );
        }
    }

    public static function processSingleImage(int $product_id, string $image_url, bool $is_featured = false) {
        // Upload to media library
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        $filename = basename($image_url);
        
        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }
        
        file_put_contents($file, $image_data);
        
        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $file);
        
        // Set as featured if needed
        if ($is_featured) {
            update_post_meta($product_id, '_thumbnail_id', $attach_id);
        } else {
            // Add to product gallery
            $gallery = get_post_meta($product_id, '_product_image_gallery', true);
            $gallery = $gallery ? explode(',', $gallery) : [];
            $gallery[] = $attach_id;
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery));
        }
    }

    private static function getOrCreateTerm(string $name, string $taxonomy): int {
        $term = get_term_by('name', $name, $taxonomy);
        if ($term) {
            return $term->term_id;
        }
        
        $new_term = wp_insert_term($name, $taxonomy);
        return $new_term['term_id'];
    }

    private static function createAttributesFromVariants(array $variants): array {
        $attributes = [];
        
        foreach ($variants as $variant) {
            foreach ($variant['attributes'] as $name => $value) {
                if (!isset($attributes[$name])) {
                    $attributes[$name] = [];
                }
                $attributes[$name][] = $value;
            }
        }
        
        return array_map(function($values) {
            return [
                'name' => $name,
                'value' => implode('|', array_unique($values)),
                'visible' => true,
                'variation' => true
            ];
        }, $attributes);
    }
}
