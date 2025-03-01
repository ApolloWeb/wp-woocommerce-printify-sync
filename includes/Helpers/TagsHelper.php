<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class TagsHelper {
    public function map_tags_to_woocommerce( $product_id, $tags ) {
        $tag_ids = [];
        foreach ( $tags as $tag ) {
            $tag_id = term_exists( $tag['name'], 'product_tag' );
            if ( ! $tag_id ) {
                $tag_id = wp_insert_term( $tag['name'], 'product_tag' );
            }
            $tag_ids[] = (int) $tag_id['term_id'];
        }
        wp_set_object_terms( $product_id, $tag_ids, 'product_tag' );
    }
}