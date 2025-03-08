<?php

namespace ApolloWeb\WpWooCommercePrintifySync\Controllers;

use ApolloWeb\WpWooCommercePrintifySync\Abstracts\BaseController;
use ApolloWeb\WpWooCommercePrintifySync\Helpers\Logger;
use ApolloWeb\WpWooCommercePrintifySync\Helpers\Settings;
use ApolloWeb\WpWooCommercePrintifySync\Services\ProductSyncService;
use ApolloWeb\WpWooCommercePrintifySync\Services\OrderSyncService;
use ApolloWeb\WpWooCommercePrintifySync\Services\ApiClient;

class PrintifySyncController extends BaseController
{
    protected $productSyncService;
    protected $orderSyncService;

    public function __construct()
    {
        $apiUrl = Settings::get('printify_api_url');
        $apiKey = Settings::get('printify_api_key');
        $apiClient = new ApiClient($apiUrl, $apiKey);

        $this->productSyncService = new ProductSyncService($apiClient);
        $this->orderSyncService = new OrderSyncService($apiClient);
    }

    public function syncProducts()
    {
        $this->productSyncService->syncProducts();
    }

    public function syncOrders()
    {
        $this->orderSyncService->syncOrders();
    }
}