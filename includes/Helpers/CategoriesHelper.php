<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class CategoriesHelper {
    public function map_categories_to_woocommerce( $product_id, $categories ) {
        $category_ids = [];
        foreach ( $categories as $category ) {
            $category_id = term_exists( $category['name'], 'product_cat' );
            if ( ! $category_id ) {
                $category_id = wp_insert_term( $category['name'], 'product_cat' );
            }
            $category_ids[] = (int) $category_id['term_id'];
        }
        wp_set_object_terms( $product_id, $category_ids, 'product_cat' );
    }
}