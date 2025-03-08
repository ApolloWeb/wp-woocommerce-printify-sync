<?php

use ApolloWeb\WpWooCommercePrintifySync\Services\ProductSyncService;
use ApolloWeb\WpWooCommercePrintifySync\Services\ApiClient;
use PHPUnit\Framework\TestCase;

class ProductSyncServiceTest extends TestCase
{
    public function testSyncProducts()
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('fetchPrintifyProducts')->willReturn([
            ['name' => 'Test Product', 'price' => '10.00', 'description' => 'Test description', 'short_description' => 'Short description', 'categories' => [], 'images' => []],
        ]);

        $productSyncService = new ProductSyncService($apiClient);
        $productSyncService->syncProducts();

        // Assertions to verify product sync logic
    }
}