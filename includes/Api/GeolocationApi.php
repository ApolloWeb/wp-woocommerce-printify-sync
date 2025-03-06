<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiHelper;

class GeolocationApi {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getGeolocation($ip) {
        $url = "https://api.ipgeolocation.io/ipgeo?apiKey={$this->apiKey}&ip={$ip}";
        return ApiHelper::get($url);
    }
}