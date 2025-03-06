<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiHelper;

class CurrencyConversionApi {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getExchangeRate($currency) {
        $url = "https://freecurrencyapi.com/api/v1/rates?apikey={$this->apiKey}&base_currency={$currency}";
        return ApiHelper::get($url);
    }
}