<?php

namespace ApolloWeb\WpWooCommercePrintifySync\Services;

use ApolloWeb\WpWooCommercePrintifySync\Helpers\Logger;

class ApiClient
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct($apiUrl, $apiKey)
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function fetchPrintifyProducts()
    {
        // Logic to fetch products from Printify API
        Logger::log('Fetching products from Printify API.');
        return []; // Replace with actual API call and response
    }

    public function fetchPrintifyOrders()
    {
        // Logic to fetch orders from Printify API
        Logger::log('Fetching orders from Printify API.');
        return []; // Replace with actual API call and response
    }
}