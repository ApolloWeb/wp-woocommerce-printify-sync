<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\AttributeHandlerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Exceptions\AttributeException;

class AttributeHandler implements AttributeHandlerInterface
{
    private string $currentTime;
    private string $currentUser;

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime; // 2025-03-15 18:09:19
        $this->currentUser = $currentUser; // ApolloWeb
    }

    public function createAttribute(string $name, array $options = []): int
    {
        global $wpdb;

        $attributeName = wc_sanitize_taxonomy_name($name);
        $attributeLabel = $options['label'] ?? $name;

        $attributeId = wc_attribute_taxonomy_id_by_name($attributeName);
        if ($attributeId) {
            return $attributeId;
        }

        $args = wp_parse_args($options, [
            'name' => $attributeName,
            'slug' => $attributeName,
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false
        ]);

        $result = wc_create_attribute($args);
        
        if (is_wp_error($result)) {
            throw new AttributeException(
                "Failed to create attribute: {$result->get_error_message()}"
            );
        }

        // Register the taxonomy
        register_taxonomy(
            'pa_' . $attributeName,
            ['product'],
            [
                'label' => $attributeLabel,
                'rewrite' => ['slug' => $attributeName],
                'hierarchical' => true
            ]
        );

        return $result;
    }

    public function addAttributeToProduct(
        int $productId,
        string $attributeName,
        array $values,
        bool $isVariation = false
    ): void {
        $attributeTaxonomy = 'pa_' . wc_sanitize_taxonomy_name($attributeName);
        $productAttributes = get_post_meta($productId, '_product_attributes', true) ?: [];

        // Create terms if they don't exist
        $termIds = [];
        foreach ($values as $value) {
            $term = get_term_by('name', $value, $attributeTaxonomy);
            if (!$term) {
                $result = wp_insert_term($value, $attributeTaxonomy);
                if (!is_wp_error($result)) {
                    $termIds[] = $result['term_id'];
                }
            } else {
                $termIds[] = $term->term_id;
            }
        }

        // Assign terms to product
        wp_set_object_terms($productId, $termIds, $attributeTaxonomy);

        // Update product attributes
        $productAttributes[$attributeTaxonomy] = [
            'name' => $attributeTaxonomy,
            'value' => '',
            'position' => count($productAttributes),
            'is_visible' => 1,
            'is_variation' => $isVariation ? 1 : 0,
            'is_taxonomy' => 1
        ];

        update_post_meta($productId, '_product_attributes', $productAttributes);
    }

    public function createVariation(int $productId, array $attributes, array $data): int
    {
        $variation = new \WC_Product_Variation();
        $variation->set_parent_id($productId);

        foreach ($attributes as $taxonomy => $value) {
            $variation->set_attribute($taxonomy, $value);
        }

        if (isset($data['sku'])) {
            $variation->set_sku($data['sku']);
        }

        if (isset($data['regular_price'])) {
            $variation->set_regular_price($data['regular_price']);
        }

        if (isset($data['sale_price'])) {
            $variation->set_sale_price($data['sale_price']);
        }

        $variation->set_manage_stock(false);
        $variation->set_stock_status('instock');

        $variationId = $variation->save();

        if (!$variationId) {
            throw new AttributeException("Failed to create variation");
        }

        // Add meta data
        update_post_meta($variationId, '_wpwps_created_at', $this->currentTime);
        update_post_meta($variationId, '_wpwps_created_by', $this->currentUser);

        return $variationId;
    }
}