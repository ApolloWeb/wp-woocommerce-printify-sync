<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class VariantsHelper {
    public function map_variants_to_woocommerce( $product_id, $variants ) {
        foreach ( $variants as $variant ) {
            $variant_id = $this->get_variant_id( $product_id, $variant['id'] );

            $attributes = [];
            foreach ( $variant['attributes'] as $attribute ) {
                $taxonomy = 'pa_' . wc_sanitize_taxonomy_name( $attribute['name'] );
                if ( ! taxonomy_exists( $taxonomy ) ) {
                    register_taxonomy(
                        $taxonomy,
                        'product',
                        [
                            'hierarchical' => true,
                            'label'        => ucfirst( $attribute['name'] ),
                            'query_var'    => true,
                            'rewrite'      => [ 'slug' => sanitize_title( $attribute['name'] ) ],
                        ]
                    );
                }

                $term_exists = term_exists( $attribute['value'], $taxonomy );
                if ( ! $term_exists ) {
                    $term_exists = wp_insert_term( $attribute['value'], $taxonomy );
                }
                $attributes[] = [
                    'name'         => $taxonomy,
                    'value'        => $attribute['value'],
                    'is_visible'   => true,
                    'is_variation' => true,
                    'is_taxonomy'  => true,
                ];
            }

            $variation_data = [
                'post_title'  => get_the_title( $product_id ) . ' - ' . implode( ', ', wp_list_pluck( $attributes, 'value' ) ),
                'post_status' => 'publish',
                'post_parent' => $product_id,
                'post_type'   => 'product_variation',
                'meta_input'  => [
                    '_price'   => $variant['price'],
                    '_sku'     => $variant['sku'],
                    '_stock'   => $variant['stock'],
                    '_variant' => $variant['id'],
                ],
            ];

            if ( $variant_id ) {
                $variation_data['ID'] = $variant_id;
                wp_update_post( $variation_data );
            } else {
                $variant_id = wp_insert_post( $variation_data );
            }

            foreach ( $attributes as $attribute ) {
                update_post_meta( $variant_id, 'attribute_' . $attribute['name'], $attribute['value'] );
            }
        }
    }

    private function get_variant_id( $product_id, $variant_id ) {
        global $wpdb;
        $query = $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_variant' AND meta_value = %s", $variant_id );
        return $wpdb->get_var( $query );
    }
}