<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class ApiHelper {
    public static function get($url, $headers = []) {
        $response = wp_remote_get($url, ['headers' => $headers]);
        return wp_remote_retrieve_body($response);
    }

    public static function post($url, $body = [], $headers = []) {
        $response = wp_remote_post($url, ['body' => $body, 'headers' => $headers]);
        return wp_remote_retrieve_body($response);
    }
}