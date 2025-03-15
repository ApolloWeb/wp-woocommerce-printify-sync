<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Entities\PrintifyProduct;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LoggerAwareTrait;

class ProductImporter extends BaseService
{
    use LoggerAwareTrait;

    public function importToWooCommerce(PrintifyProduct $product): int
    {
        // ... existing product creation code ...

        // Handle variants
        if (count($product->getVariants()) > 1) {
            $this->createVariableProduct($productId, $product);
        } else {
            $this->createSimpleProduct($productId, $product);
        }

        return $productId;
    }

    private function createSimpleProduct(int $productId, PrintifyProduct $product): void
    {
        $variant = $product->getVariants()[0];

        // Use retail price directly
        $price = $variant->getRetailPrice();

        update_post_meta($productId, '_regular_price', $price);
        update_post_meta($productId, '_price', $price);
        update_post_meta($productId, '_sku', $variant->getSku());
        update_post_meta($productId, '_weight', $variant->getGrams() / 1000); // Convert to kg
        update_post_meta($productId, '_manage_stock', 'no');
        update_post_meta($productId, '_stock_status', 'instock');

        wp_set_object_terms($productId, 'simple', 'product_type');
    }

    private function createVariableProduct(int $productId, PrintifyProduct $product): void
    {
        wp_set_object_terms($productId, 'variable', 'product_type');

        // Create attributes from variant options
        $attributes = $this->createAttributesFromVariants($product->getVariants());
        update_post_meta($productId, '_product_attributes', $attributes);

        // Create variations
        foreach ($product->getVariants() as $variant) {
            if (!$variant->isEnabled()) {
                continue;
            }

            $variationId = $this->createVariation($productId, $variant, $attributes);

            // Set retail price for variation
            update_post_meta($variationId, '_regular_price', $variant->getRetailPrice());
            update_post_meta($variationId, '_price', $variant->getRetailPrice());
            update_post_meta($variationId, '_sku', $variant->getSku());
            update_post_meta($variationId, '_weight', $variant->getGrams() / 1000);
            update_post_meta($variationId, '_manage_stock', 'no');
            update_post_meta($variationId, '_stock_status', 'instock');

            // Store Printify metadata
            update_post_meta($variationId, '_printify_variant_id', $variant->getId());
            update_post_meta($variationId, '_printify_is_shipping_enabled', $variant->isShippingEnabled());
        }
    }

    private function createAttributesFromVariants(array $variants): array
    {
        $attributes = [];
        
        // Collect all possible options for each attribute
        foreach ($variants as $variant) {
            foreach ($variant->getOptions() as $name => $value) {
                $attributes[$name][] = $value;
            }
        }

        // Format attributes for WooCommerce
        $wooAttributes = [];
        foreach ($attributes as $name => $values) {
            $sanitizedName = sanitize_title($name);
            $wooAttributes[$sanitizedName] = [
                'name' => $name,
                'value' => implode('|', array_unique($values)),
                'position' => 0,
                'is_visible' => 1,
                'is_variation' => 1,
                'is_taxonomy' => 0
            ];
        }

        return $wooAttributes;
    }

    private function createVariation(int $productId, ProductVariant $variant, array $attributes): int
    {
        $variation = [
            'post_title' => 'Product #' . $productId . ' Variation',
            'post_name' => 'product-' . $productId . '-variation',
            'post_status' => 'publish',
            'post_parent' => $productId,
            'post_type' => 'product_variation',
            'guid' => ''
        ];

        $variationId = wp_insert_post($variation);

        // Set variation attributes
        foreach ($variant->getOptions() as $name => $value) {
            $attributeName = sanitize_title($name);
            update_post_meta(
                $variationId,
                'attribute_' . $attributeName,
                sanitize_title($value)
            );
        }

        return $variationId;
    }
}