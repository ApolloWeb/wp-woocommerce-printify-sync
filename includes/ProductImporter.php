/**
 * ProductImporter class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

declare(strict_types=1);

namespace WP_WooCommerce_Printify_Sync\Includes;

if (!defined('ABSPATH')) {
    exit;
}

use WP_WooCommerce_Printify_Sync\Includes\Helpers\TagsHelper;
use WP_WooCommerce_Printify_Sync\Includes\Helpers\CategoriesHelper;
use WP_WooCommerce_Printify_Sync\Includes\Helpers\VariantsHelper;
use WP_WooCommerce_Printify_Sync\Includes\Helpers\ImagesHelper;

class ProductImporter
{
    protected PrintifyAPI $api;

    protected Logger $logger;

    public function __construct(PrintifyAPI $apiInstance)
    {
        $this->api    = $apiInstance;
        $this->logger = new Logger();
    }

    public function runImport(): void
    {
        $page     = 1;
        $products = $this->api->getProducts($page);

        while (is_array($products) && !empty($products)) {
            $this->processProductsBatch($products);
            $page++;
            $products = $this->api->getProducts($page);
        }
    }

    protected function processProductsBatch(array $products): void
    {
        foreach ($products as $product) {
            $this->processSingleProduct($product);
        }
    }

    protected function processSingleProduct(array $printifyProduct): void
    {
        // Create a new WooCommerce variable product.
        $wcProduct = new \WC_Product_Variable();

        // Map basic product data.
        $wcProduct->set_name($printifyProduct['name'] ?? 'Unnamed Product');
        $wcProduct->set_status('publish');
        $wcProduct->set_sku($printifyProduct['id']);

        // Save product to get its ID.
        $productId = $wcProduct->save();

        // Add metadata to link the product to Printify.
        update_post_meta($productId, '_print_provider', 'printify');
        update_post_meta($productId, '_printify_product_id', $printifyProduct['id']);

        // Process tags.
        $tags = TagsHelper::processTags($printifyProduct['tags'] ?? []);
        if (!empty($tags)) {
            wp_set_object_terms($productId, $tags, 'product_tag');
        }

        // Process categories (assuming a custom taxonomy 'product_type').
        $categories = CategoriesHelper::processCategories($printifyProduct['categories'] ?? []);
        if (!empty($categories)) {
            wp_set_object_terms($productId, $categories, 'product_type');
        }

        // Process variants and attributes.
        $variantsData = VariantsHelper::processVariants($printifyProduct['variants'] ?? []);
        // Implementation for creating WooCommerce product variations goes here.

        // Process images.
        $images = ImagesHelper::processImages($printifyProduct['images'] ?? []);
        if (!empty($images)) {
            $wcProduct->set_image_id($images['main']);
            $wcProduct->set_gallery_image_ids($images['gallery']);
        }

        // Save the WooCommerce product with all updated data.
        $wcProduct->save();

        $this->logger->log(
            sprintf(
                'Imported product: %s (Printify ID: %s)',
                $printifyProduct['name'] ?? 'Unnamed',
                $printifyProduct['id']
            ),
            'info'
        );
    }
}
