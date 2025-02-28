/**
 * ProductImport class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class ProductImport
{
    protected PrintifyAPI $api;

    protected Logger $logger;

    protected int $chunkSize = 10;

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
        // Optionally update progress or checkpoint here.
    }

    protected function processSingleProduct(array $printifyProduct): void
    {
        // Create a new WooCommerce variable product.
        $wcProduct = new WC_Product_Variable();

        // Map basic product data.
        $wcProduct->set_name($printifyProduct['name'] ?? 'Unnamed Product');
        $wcProduct->set_status('publish');

        // Set SKU and save meta: use Printify product id as SKU.
        $wcProduct->set_sku($printifyProduct['id']);
        $productId = $wcProduct->save();

        update_post_meta($productId, '_print_provider', 'printify');
        update_post_meta($productId, '_printify_product_id', $printifyProduct['id']);

        // Process product tags.
        $tags = TagsHelper::processTags($printifyProduct['tags'] ?? []);
        if (!empty($tags)) {
            wp_set_object_terms($productId, $tags, 'product_tag');
        }

        // Process product categories (limit to 2 hierarchy levels).
        $categories = CategoriesHelper::processCategories($printifyProduct['categories'] ?? []);
        if (!empty($categories)) {
            // Assuming the custom taxonomy is 'product_type'
            wp_set_object_terms($productId, $categories, 'product_type');
        }

        // Process variants and attributes.
        $variantsData = VariantsHelper::processVariants($printifyProduct['variants'] ?? []);
        // Create the WooCommerce variations based on $variantsData as needed.
        // (Implementation depends on your WooCommerce setup.)

        // Process images via ImagesHelper.
        $images = ImagesHelper::processImages($printifyProduct['images'] ?? []);
        if (!empty($images)) {
            $wcProduct->set_image_id($images['main']);
            $wcProduct->set_gallery_image_ids($images['gallery']);
        }

        // Save the WooCommerce product.
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
