<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class ApiRequestHelper
{
    public static function getRequest($url, $headers)
    {
        $response = wp_remote_get($url, [
            'headers' => $headers,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public static function postRequest($url, $headers, $body)
    {
        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public static function putRequest($url, $headers, $body)
    {
        $response = wp_remote_request($url, [
            'method'  => 'PUT',
            'headers' => $headers,
            'body'    => json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public static function deleteRequest($url, $headers)
    {
        $response = wp_remote_request($url, [
            'method'  => 'DELETE',
            'headers' => $headers,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}