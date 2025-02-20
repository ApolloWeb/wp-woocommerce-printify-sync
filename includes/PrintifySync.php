<?php

namespace ApolloWeb\WooCommercePrintifySync;

class PrintifyAPI
{
    private $apiClient;

    public function __construct()
    {
        $apiKey = get_option('printify_api_key');
        $this->apiClient = new APIClient($apiKey);
    }

    public function getShops()
    {
        return $this->apiClient->getShops();
    }

    public function getProductsByShop($shopId)
    {
        return $this->apiClient->getProducts($shopId);
    }

    public function getProductDetails($productId)
    {
        return $this->apiClient->getProductDetails($productId);
    }

    public function getStockLevels($productId)
    {
        return $this->apiClient->getStockLevels($productId);
    }

    public function getVariants($productId)
    {
        return $this->apiClient->getVariants($productId);
    }

    public function getTags($productId)
    {
        return $this->apiClient->getTags($productId);
    }

    public function getCategories($productId)
    {
        return $this->apiClient->getCategories($productId);
    }
}