<?php

namespace ApolloWeb\WPWoocomercePrintifySync;

class PrintifyAPI {
    private $api_key;
    private $api_endpoint;
    private $last_error;

    public function __construct() {
        $this->api_key = get_option( 'wwps_printify_api_key' );
        $this->api_endpoint = get_option( 'wwps_printify_api_endpoint', 'https://api.printify.com/v1/' );
    }

    public function get_shops() {
        $response = wp_remote_get( $this->api_endpoint . 'shops.json', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json;charset=utf-8'
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            $this->last_error = $response->get_error_message();
            error_log( 'Printify API Error: ' . $this->last_error );
            return array();
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['error'] ) ) {
            $this->last_error = $data['error'];
            error_log( 'Printify API Error: ' . $this->last_error );
            return array();
        }

        return isset( $data ) ? $data : array();
    }

    public function get_products($shop_id, $page = 1, $limit = 50) {
        $response = wp_remote_get( "{$this->api_endpoint}shops/{$shop_id}/products.json?page={$page}&limit={$limit}", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json;charset=utf-8'
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            $this->last_error = $response->get_error_message();
            error_log( 'Printify API Error: ' . $this->last_error );
            return array();
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['error'] ) ) {
            $this->last_error = $data['error'];
            error_log( 'Printify API Error: ' . $this->last_error );
            return array();
        }

        return isset( $data ) ? $data : array();
    }

    public function get_last_error() {
        return $this->last_error;
    }

    // Other methods: get_product_details(), get_stock_levels(), get_variants(), get_tags(), get_categories()
}