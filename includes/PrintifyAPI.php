<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class PrintifyAPI {
    private $api_key;
    private $base_url = 'https://api.printify.com/v1/';
    private $rate_limit = 60; // Set according to Printify API rate limits
    private $rate_limit_remaining;
    private $rate_limit_reset;

    public function __construct() {
        $this->api_key = get_option( 'wp_woocommerce_printify_sync_api_key' );
    }

    public function request( $endpoint, $method = 'GET', $body = [] ) {
        if ( $this->rate_limit_remaining === 0 && time() < $this->rate_limit_reset ) {
            return new \WP_Error( 'rate_limit_exceeded', __( 'Printify API rate limit exceeded. Please try again later.', 'wp-woocommerce-printify-sync' ) );
        }

        $url = $this->base_url . $endpoint;
        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
        ];

        if ( ! empty( $body ) ) {
            $args['body'] = json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $headers = wp_remote_retrieve_headers( $response );
        $this->rate_limit_remaining = isset( $headers['x-ratelimit-remaining'] ) ? (int) $headers['x-ratelimit-remaining'] : $this->rate_limit;
        $this->rate_limit_reset = isset( $headers['x-ratelimit-reset'] ) ? (int) $headers['x-ratelimit-reset'] : time();

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    public function get_shops() {
        return $this->request( 'shops.json' );
    }

    public function get_products_by_shop( $shop_id ) {
        return $this->request( 'shops/' . $shop_id . '/products.json' );
    }

    public function get_product_details( $product_id ) {
        return $this->request( 'products/' . $product_id . '.json' );
    }

    public function get_stock_levels( $product_id ) {
        return $this->request( 'products/' . $product_id . '/stock_levels.json' );
    }

    public function get_variants( $product_id ) {
        return $this->request( 'products/' . $product_id . '/variants.json' );
    }

    public function get_tags( $product_id ) {
        return $this->request( 'products/' . $product_id . '/tags.json' );
    }

    public function get_categories( $product_id ) {
        return $this->request( 'products/' . $product_id . '/categories.json' );
    }
}