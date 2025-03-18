<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Product;

class ProductMapper {
    public function mapCategories(array $productData): array {
        if (!empty($productData['product_type'])) {
            return array_map('trim', explode(',', $productData['product_type']));
        }
        return [];
    }
    public function mapTags(array $productData): array {
        if (!empty($productData['tags'])) {
            return array_map('trim', explode(',', $productData['tags']));
        }
        return [];
    }
    public function mapAttributes(array $productData): array {
        $attributes = [];
        if (!empty($productData['variants']) && is_array($productData['variants'])) {
            foreach ($productData['variants'] as $variant) {
                foreach ($variant as $key => $value) {
                    if (in_array($key, ['size', 'color', 'material'], true)) {
                        $attributes[$key][] = $value;
                    }
                }
            }
            foreach ($attributes as $key => $options) {
                $attributes[$key] = array_values(array_unique($options));
            }
        }
        return $attributes;
    }
}
