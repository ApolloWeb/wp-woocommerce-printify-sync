<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiHelper;

class PrintifyApi {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getProducts() {
        $url = "https://api.printify.com/v1/products.json";
        $headers = ['Authorization' => "Bearer {$this->apiKey}"];
        return ApiHelper::get($url, $headers);
    }

    public function createOrder($orderData) {
        $url = "https://api.printify.com/v1/orders.json";
        $headers = ['Authorization' => "Bearer {$this->apiKey}"];
        return ApiHelper::post($url, $orderData, $headers);
    }
}