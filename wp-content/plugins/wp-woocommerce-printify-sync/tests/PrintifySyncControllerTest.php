<?php

use ApolloWeb\WpWooCommercePrintifySync\Controllers\PrintifySyncController;
use ApolloWeb\WpWooCommercePrintifySync\Services\ProductSyncService;
use ApolloWeb\WpWooCommercePrintifySync\Services\OrderSyncService;
use PHPUnit\Framework\TestCase;

class PrintifySyncControllerTest extends TestCase
{
    protected $productSyncService;
    protected $orderSyncService;
    protected $printifySyncController;

    protected function setUp(): void
    {
        $apiClientMock = $this->createMock(\ApolloWeb\WpWooCommercePrintifySync\Services\ApiClient::class);
        $this->productSyncService = new ProductSyncService($apiClientMock);
        $this->orderSyncService = new OrderSyncService($apiClientMock);
        $this->printifySyncController = new PrintifySyncController($this->productSyncService, $this->orderSyncService);
    }

    public function testSyncProducts()
    {
        $this->productSyncService->expects($this->once())->method('syncProducts');
        $this->printifySyncController->syncProducts();
    }

    public function testSyncOrders()
    {
        $this->orderSyncService->expects($this->once())->method('syncOrders');
        $this->printifySyncController->syncOrders();
    }
}