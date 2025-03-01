<?php

namespace ApolloWeb\WPWoocomercePrintifySync;

class ProductImport {
    private $api;
    private $shop_id;

    public function __construct($shop_id) {
        $this->api = new PrintifyAPI();
        $this->shop_id = $shop_id;
    }

    public function import_products() {
        $page = 1;
        $limit = 50;
        do {
            $products = $this->api->get_products($this->shop_id, $page, $limit);
            foreach ($products as $product) {
                $this->process_product($product);
            }
            $page++;
        } while (count($products) == $limit);
    }

    private function process_product($product) {
        // Check if the product already exists.
        $existing_product_id = wc_get_product_id_by_sku($product['id']);
        if ($existing_product_id) {
            $this->update_product($existing_product_id, $product);
        } else {
            $this->create_product($product);
        }
    }

    private function create_product($product) {
        // Create WooCommerce product.
        $wc_product = new \WC_Product_Variable();
        $wc_product->set_name($product['title']);
        $wc_product->set_sku($product['id']);
        $wc_product->set_description($product['description']);
        $wc_product->set_catalog_visibility('visible');
        $wc_product->set_status('publish');
        $wc_product->set_price($product['retail_price']);
        $wc_product->set_currency('GBP');

        $wc_product_id = $wc_product->save();

        // Store provider ID and Printify product ID.
        update_post_meta($wc_product_id, '_print_provider_id', $this->shop_id);
        update_post_meta($wc_product_id, '_printify_product_id', $product['id']);

        // Process variants, images, tags, and categories.
        VariantsHelper::map_variants_to_woocommerce($wc_product_id, $product['variants']);
        ImagesHelper::handle_images($wc_product_id, $product['images']);
        TagsHelper::map_tags_to_woocommerce($wc_product_id, $product['tags']);
        CategoriesHelper::map_categories_to_woocommerce($wc_product_id, $product['categories']);
    }

    private function update_product($product_id, $product) {
        // Update WooCommerce product details.
        $wc_product = wc_get_product($product_id);
        $wc_product->set_name($product['title']);
        $wc_product->set_description($product['description']);
        $wc_product->set_price($product['retail_price']);
        $wc_product->set_currency('GBP');
        $wc_product->save();

        // Update provider ID and Printify product ID if needed.
        update_post_meta($product_id, '_print_provider_id', $this->shop_id);
        update_post_meta($product_id, '_printify_product_id', $product['id']);

        // Process variants, images, tags, and categories.
        VariantsHelper::map_variants_to_woocommerce($product_id, $product['variants']);
        ImagesHelper::handle_images($product_id, $product['images']);
        TagsHelper::map_tags_to_woocommerce($product_id, $product['tags']);
        CategoriesHelper::map_categories_to_woocommerce($product_id, $product['categories']);
    }
}