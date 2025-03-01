<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

use WP_Error;

class ProductImport {
    private $api;
    private $chunk_size = 10;

    public function __construct() {
        $this->api = new PrintifyAPI();
    }

    public function import_products() {
        $shop_id = get_option( 'wp_woocommerce_printify_sync_selected_shop' );
        if ( ! $shop_id ) {
            return new WP_Error( 'no_shop_selected', __( 'No shop selected.', 'wp-woocommerce-printify-sync' ) );
        }

        $products = $this->api->get_products_by_shop( $shop_id );

        if ( is_wp_error( $products ) ) {
            return $products;
        }

        $total_products = count( $products );
        $chunks = array_chunk( $products, $this->chunk_size );

        foreach ( $chunks as $index => $chunk ) {
            as_enqueue_async_action( 'wp_woocommerce_printify_sync_import_chunk', [ $chunk ], 'wp-woocommerce-printify-sync' );
        }

        return count($chunks);
    }

    public function import_chunk( $products ) {
        foreach ( $products as $product ) {
            $this->import_product( $product );
        }
    }

    private function import_product( $product ) {
        $product_id = wc_get_product_id_by_sku( $product['id'] );
        $is_update = $product_id ? true : false;

        $product_data = [
            'post_title'   => $product['title'],
            'post_content' => $product['description'],
            'post_status'  => 'publish',
            'post_type'    => 'product',
            'meta_input'   => [
                '_print_provider'      => 'printify',
                '_printify_product_id' => $product['id'],
            ],
        ];

        if ( $is_update ) {
            $product_data['ID'] = $product_id;
            wp_update_post( $product_data );
        } else {
            $product_id = wp_insert_post( $product_data );
        }

        $this->update_product_meta( $product_id, $product );
        $this->update_product_images( $product_id, $product['images'] );
        $this->update_product_variants( $product_id, $product['variants'] );
        $this->update_product_categories( $product_id, $product['categories'] );
        $this->update_product_tags( $product_id, $product['tags'] );
    }

    private function update_product_meta( $product_id, $product ) {
        update_post_meta( $product_id, '_regular_price', $product['price'] );
        update_post_meta( $product_id, '_stock', $product['stock'] );
        update_post_meta( $product_id, '_sku', $product['id'] );
    }

    private function update_product_images( $product_id, $images ) {
        $image_helper = new Helpers\ImagesHelper();
        $image_helper->handle_images( $product_id, $images );
    }

    private function update_product_variants( $product_id, $variants ) {
        $variant_helper = new Helpers\VariantsHelper();
        $variant_helper->map_variants_to_woocommerce( $product_id, $variants );
    }

    private function update_product_categories( $product_id, $categories ) {
        $category_helper = new Helpers\CategoriesHelper();
        $category_helper->map_categories_to_woocommerce( $product_id, $categories );
    }

    private function update_product_tags( $product_id, $tags ) {
        $tag_helper = new Helpers\TagsHelper();
        $tag_helper->map_tags_to_woocommerce( $product_id, $tags );
    }
}