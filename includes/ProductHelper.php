<?php

namespace ApolloWeb\WooCommercePrintifySync;

class ProductHelper
{
    public function mapTagsToWooCommerce($tags, $productId)
    {
        wp_set_object_terms($productId, $tags, 'product_tag');
    }

    public function mapCategoriesToWooCommerce($categories, $productId)
    {
        wp_set_object_terms($productId, $categories, 'product_cat');
    }

    public function mapVariantsToWooCommerce($variants, $productId)
    {
        foreach ($variants as $variant) {
            $variation = new \WC_Product_Variation();
            $variation->set_parent_id($productId);
            $variation->set_attributes($variant['attributes']);
            $variation->set_regular_price($variant['price']);
            $variation->save();
        }
    }

    public function handleImages()
    {
        // Implementation for handling images.
    }

    public function queueImageUpload()
    {
        // Implementation for queuing image upload.
    }

    public function uploadImageToCloudflareR2()
    {
        // Implementation for uploading image to Cloudflare R2.
    }
}