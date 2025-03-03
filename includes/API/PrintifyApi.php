<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiRequestHelper;

class PrintifyApi
{
    private $apiKey;
    private $apiUrl = 'https://api.printify.com/v1/';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json'
        ];
    }

    public function getShops()
    {
        $url = $this->apiUrl . 'shops.json';
        return ApiRequestHelper::getRequest($url, $this->getHeaders());
    }

    public function getProducts($shopId)
    {
        $url = $this->apiUrl . 'shops/' . $shopId . '/products.json';
        return ApiRequestHelper::getRequest($url, $this->getHeaders());
    }

    public function getProduct($shopId, $productId)
    {
        $url = $this->apiUrl . 'shops/' . $shopId . '/products/' . $productId . '.json';
        return ApiRequestHelper::getRequest($url, $this->getHeaders());
    }

    public function createProduct($shopId, $productData)
    {
        $url = $this->apiUrl . 'shops/' . $shopId . '/products.json';
        return ApiRequestHelper::postRequest($url, $this->getHeaders(), $productData);
    }

    public function updateProduct($shopId, $productId, $productData)
    {
        $url = $this->apiUrl . 'shops/' . $shopId . '/products/' . $productId . '.json';
        return ApiRequestHelper::putRequest($url, $this->getHeaders(), $productData);
    }

    public function deleteProduct($shopId, $productId)
    {
        $url = $this->apiUrl . 'shops/' . $shopId . '/products/' . $productId . '.json';
        return ApiRequestHelper::deleteRequest($url, $this->getHeaders());
    }

    // Other methods to interact with Printify API endpoints...
}