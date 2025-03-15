<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class TaxonomyManager
{
    private string $currentTime;
    private string $currentUser;
    private const MAX_DEPTH = 2;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:25:31';
        $this->currentUser = 'ApolloWeb';

        add_action('init', [$this, 'registerProductTypeTaxonomy']);
    }

    public function registerProductTypeTaxonomy(): void
    {
        register_taxonomy(
            'product_type_category',
            ['product'],
            [
                'hierarchical' => true,
                'labels' => [
                    'name' => 'Product Types',
                    'singular_name' => 'Product Type',
                    'menu_name' => 'Product Types',
                    'all_items' => 'All Product Types',
                    'parent_item' => 'Parent Product Type',
                    'parent_item_colon' => 'Parent Product Type:',
                    'new_item_name' => 'New Product Type Name',
                    'add_new_item' => 'Add New Product Type',
                    'edit_item' => 'Edit Product Type',
                    'update_item' => 'Update Product Type',
                    'view_item' => 'View Product Type',
                    'search_items' => 'Search Product Types'
                ],
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'product-type'],
                'show_in_rest' => true,
            ]
        );
    }

    public function assignProductType(int $productId, string $type, ?string $subtype = null): void
    {
        // Ensure we're using HPOS if enabled
        $product = wc_get_product($productId);
        if (!$product) {
            return;
        }

        // Get or create parent term
        $parentTerm = $this->getOrCreateTerm($type);
        
        if ($subtype) {
            // Get or create child term
            $childTerm = $this->getOrCreateTerm($subtype, $parentTerm['term_id']);
            $termId = $childTerm['term_id'];
        } else {
            $termId = $parentTerm['term_id'];
        }

        // Use WooCommerce's CRUD methods
        $product->set_category_ids([$termId]);
        $product->save();
    }

    private function getOrCreateTerm(string $name, ?int $parentId = 0): array
    {
        $term = term_exists($name, 'product_type_category', $parentId);
        
        if (!$term) {
            $term = wp_insert_term(
                $name,
                'product_type_category',
                [
                    'parent' => $parentId,
                    'slug' => sanitize_title($name)
                ]
            );

            if (is_wp_error($term)) {
                throw new \Exception($term->get_error_message());
            }

            // Add custom meta
            add_term_meta($term['term_id'], '_created_at', $this->currentTime);
            add_term_meta($term['term_id'], '_created_by', $this->currentUser);
        }

        return $term;
    }

    public function getProductTypeHierarchy(): array
    {
        $hierarchy = [];
        
        // Get top-level terms
        $parentTerms = get_terms([
            'taxonomy' => 'product_type_category',
            'parent' => 0,
            'hide_empty' => false
        ]);

        if (!is_wp_error($parentTerms)) {
            foreach ($parentTerms as $parentTerm) {
                $hierarchy[$parentTerm->term_id] = [
                    'name' => $parentTerm->name,
                    'slug' => $parentTerm->slug,
                    'children' => []
                ];

                // Get children
                $childTerms = get_terms([
                    'taxonomy' => 'product_type_category',
                    'parent' => $parentTerm->term_id,
                    'hide_empty' => false
                ]);

                if (!is_wp_error($childTerms)) {
                    foreach ($childTerms as $childTerm) {
                        $hierarchy[$parentTerm->term_id]['children'][$childTerm->term_id] = [
                            'name' => $childTerm->name,
                            'slug' => $childTerm->slug
                        ];
                    }
                }
            }
        }

        return $hierarchy;
    }
}