<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ProductService {
    private $api_service;

    public function __construct() {
        $this->api_service = new ApiService();
        add_action('wp_ajax_wpwps_sync_products', [$this, 'ajaxSyncProducts']);
        add_action('wpwps_scheduled_product_sync', [$this, 'syncProducts']);
    }

    public function ajaxSyncProducts(): void {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-woocommerce-printify-sync'));
        }

        // Schedule the sync to run in the background
        as_schedule_single_action(time(), 'wpwps_scheduled_product_sync');
        
        wp_send_json_success([
            'message' => __('Product sync scheduled', 'wp-woocommerce-printify-sync')
        ]);
    }

    public function syncProducts(): void {
        $response = $this->api_service->getProducts();
        
        if (!$response['success']) {
            do_action('wpwps_log_error', 'Product sync failed', $response);
            return;
        }

        foreach ($response['data'] as $printify_product) {
            $this->syncProduct($printify_product);
        }

        do_action('wpwps_log_info', 'Product sync completed', [
            'total_products' => count($response['data'])
        ]);
    }

    private function syncProduct(array $printify_product): void {
        $product_id = $this->getProductIdByPrintifyId($printify_product['id']);
        
        if ($product_id) {
            $this->updateProduct($product_id, $printify_product);
        } else {
            $this->createProduct($printify_product);
        }
    }

    private function createProduct(array $printify_data): int {
        $product = new \WC_Product_Variable();
        
        $this->updateProductData($product, $printify_data);
        
        $product_id = $product->save();
        
        update_post_meta($product_id, '_printify_product_id', $printify_data['id']);
        update_post_meta($product_id, '_printify_provider_id', $printify_data['provider_id']);
        update_post_meta($product_id, '_printify_last_synced', current_time('mysql'));

        $this->createProductVariations($product_id, $printify_data['variants']);
        
        return $product_id;
    }

    private function updateProduct(int $product_id, array $printify_data): void {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return;
        }

        $this->updateProductData($product, $printify_data);
        
        $product->save();
        
        update_post_meta($product_id, '_printify_provider_id', $printify_data['provider_id']);
        update_post_meta($product_id, '_printify_last_synced', current_time('mysql'));

        $this->updateProductVariations($product_id, $printify_data['variants']);
    }

    private function updateProductData(\WC_Product $product, array $printify_data): void {
        $product->set_name($printify_data['title']);
        $product->set_description($printify_data['description']);
        
        // Set categories
        if (!empty($printify_data['product_type'])) {
            $this->setProductCategories($product, $printify_data['product_type']);
        }
        
        // Set tags
        if (!empty($printify_data['tags'])) {
            $product->set_tag_ids($this->createProductTags($printify_data['tags']));
        }
        
        // Set images
        if (!empty($printify_data['images'])) {
            $this->setProductImages($product, $printify_data['images']);
        }
        
        // Set attributes
        if (!empty($printify_data['variants'])) {
            $this->setProductAttributes($product, $printify_data['variants']);
        }
    }

    private function createProductVariations(int $product_id, array $variants): void {
        foreach ($variants as $variant) {
            if (!$variant['is_enabled']) {
                continue;
            }

            $variation = new \WC_Product_Variation();
            $variation->set_parent_id($product_id);
            
            $this->updateVariationData($variation, $variant);
            $variation->save();
            
            update_post_meta($variation->get_id(), '_printify_variant_id', $variant['id']);
        }
    }

    private function updateProductVariations(int $product_id, array $variants): void {
        $product = wc_get_product($product_id);
        $existing_variations = $product->get_children();

        foreach ($variants as $variant) {
            $variation_id = $this->getVariationIdByPrintifyId($product_id, $variant['id']);
            
            if ($variation_id) {
                $variation = wc_get_product($variation_id);
                $this->updateVariationData($variation, $variant);
                $variation->save();
            } else {
                if ($variant['is_enabled']) {
                    $variation = new \WC_Product_Variation();
                    $variation->set_parent_id($product_id);
                    $this->updateVariationData($variation, $variant);
                    $variation->save();
                    update_post_meta($variation->get_id(), '_printify_variant_id', $variant['id']);
                }
            }
        }
    }

    private function updateVariationData(\WC_Product_Variation $variation, array $variant_data): void {
        $variation->set_status($variant_data['is_enabled'] ? 'publish' : 'private');
        $variation->set_price($variant_data['price']);
        $variation->set_regular_price($variant_data['price']);
        $variation->set_sku($variant_data['sku']);
        
        update_post_meta($variation->get_id(), '_printify_cost_price', $variant_data['cost']);
        
        // Set attributes
        $attributes = [];
        foreach ($variant_data['attributes'] as $attribute) {
            $attributes['pa_' . sanitize_title($attribute['name'])] = $attribute['value'];
        }
        $variation->set_attributes($attributes);
        
        // Set stock status based on availability
        $variation->set_stock_status($variant_data['is_enabled'] ? 'instock' : 'outofstock');
    }

    private function getProductIdByPrintifyId(string $printify_id): ?int {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' 
            AND meta_value = %s 
            LIMIT 1",
            $printify_id
        ));
        
        return $product_id ? (int) $product_id : null;
    }

    private function getVariationIdByPrintifyId(int $product_id, string $variant_id): ?int {
        global $wpdb;
        
        $variation_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_variant_id' 
            AND meta_value = %s 
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_parent = %d 
                AND post_type = 'product_variation'
            )
            LIMIT 1",
            $variant_id,
            $product_id
        ));
        
        return $variation_id ? (int) $variation_id : null;
    }

    private function setProductCategories(\WC_Product $product, string $product_type): void {
        $categories = explode('>', $product_type);
        $category_ids = [];
        $parent_id = 0;
        
        foreach ($categories as $category_name) {
            $category_name = trim($category_name);
            $category = get_term_by('name', $category_name, 'product_cat');
            
            if (!$category) {
                $category = wp_insert_term($category_name, 'product_cat', [
                    'parent' => $parent_id
                ]);
                
                if (!is_wp_error($category)) {
                    $parent_id = $category['term_id'];
                    $category_ids[] = $category['term_id'];
                }
            } else {
                $parent_id = $category->term_id;
                $category_ids[] = $category->term_id;
            }
        }
        
        $product->set_category_ids($category_ids);
    }

    private function createProductTags(array $tags): array {
        $tag_ids = [];
        
        foreach ($tags as $tag_name) {
            $tag = get_term_by('name', $tag_name, 'product_tag');
            
            if (!$tag) {
                $tag = wp_insert_term($tag_name, 'product_tag');
                if (!is_wp_error($tag)) {
                    $tag_ids[] = $tag['term_id'];
                }
            } else {
                $tag_ids[] = $tag->term_id;
            }
        }
        
        return $tag_ids;
    }

    private function setProductImages(\WC_Product $product, array $images): void {
        $image_ids = [];
        
        foreach ($images as $index => $image_url) {
            $image_id = $this->uploadImage($image_url);
            if ($image_id) {
                if ($index === 0) {
                    $product->set_image_id($image_id);
                } else {
                    $image_ids[] = $image_id;
                }
            }
        }
        
        if (!empty($image_ids)) {
            $product->set_gallery_image_ids($image_ids);
        }
    }

    private function uploadImage(string $url): ?int {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($url);
        
        if (is_wp_error($tmp)) {
            return null;
        }
        
        $file_array = [
            'name' => basename($url),
            'tmp_name' => $tmp
        ];
        
        $id = media_handle_sideload($file_array, 0);
        
        if (is_wp_error($id)) {
            @unlink($tmp);
            return null;
        }
        
        return $id;
    }

    private function setProductAttributes(\WC_Product $product, array $variants): void {
        $attributes = [];
        
        foreach ($variants as $variant) {
            foreach ($variant['attributes'] as $attr) {
                $name = sanitize_title($attr['name']);
                if (!isset($attributes[$name])) {
                    $attributes[$name] = [
                        'name' => 'pa_' . $name,
                        'value' => [],
                        'visible' => true,
                        'variation' => true
                    ];
                }
                $attributes[$name]['value'][] = $attr['value'];
            }
        }
        
        foreach ($attributes as $attribute) {
            $attribute['value'] = array_unique($attribute['value']);
            $this->createProductAttribute($attribute['name'], $attribute['value']);
            $attributes_array[] = wc_get_attribute($attribute['name']);
        }
        
        $product->set_attributes($attributes_array);
    }

    private function createProductAttribute(string $name, array $values): void {
        $attribute_id = wc_attribute_taxonomy_id_by_name($name);
        
        if (!$attribute_id) {
            $attribute_id = wc_create_attribute([
                'name' => ucfirst(str_replace('pa_', '', $name)),
                'slug' => $name,
                'type' => 'select',
                'order_by' => 'menu_order',
                'has_archives' => false
            ]);
        }
        
        foreach ($values as $value) {
            if (!term_exists($value, 'pa_' . $name)) {
                wp_insert_term($value, 'pa_' . $name);
            }
        }
    }
}