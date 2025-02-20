<?php

namespace ApolloWeb\WooCommercePrintifySync;

class Helper
{
    public function assignCategories($productId, $categories)
    {
        wp_set_object_terms($productId, $categories, 'product_cat');
    }

    public function assignTags($productId, $tags)
    {
        wp_set_object_terms($productId, $tags, 'product_tag');
    }

    public function assignVariantsAndAttributes($productId, $variants)
    {
        $productHelper = new ProductHelper();
        $productHelper->mapVariantsToWooCommerce($variants, $productId);
    }

    public function updateSeoMeta($productId, $seoData)
    {
        if (class_exists('WPSEO_Meta')) {
            WPSEO_Meta::set_value('title', $seoData['title'], $productId);
            WPSEO_Meta::set_value('metadesc', $seoData['description'], $productId);
        }
    }

    public function optimizeImages()
    {
        // Implementation to optimize images.
    }

    public function imageExists($imageUrl)
    {
        $attachmentId = attachment_url_to_postid($imageUrl);
        return $attachmentId !== 0;
    }

    public function downloadImageCurl($imageUrl)
    {
        // Implementation to download image using cURL.
    }
}