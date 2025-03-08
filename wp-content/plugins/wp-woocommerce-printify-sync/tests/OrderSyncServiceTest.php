<?php

use ApolloWeb\WpWooCommercePrintifySync\Services\OrderSyncService;
use ApolloWeb\WpWooCommercePrintifySync\Services\ApiClient;
use PHPUnit\Framework\TestCase;

class OrderSyncServiceTest extends TestCase
{
    public function testSyncOrders()
    {
        $apiClient = $this->createMock(ApiClient::class);
        $apiClient->method('fetchPrintifyOrders')->willReturn([
            ['id' => '123', 'status' => 'pending', 'total' => '100.00', 'customer' => 'John Doe', 'items' => []],
        ]);

        $orderSyncService = new OrderSyncService($apiClient);
        $orderSyncService->syncOrders();

        // Assertions to verify order sync logic
    }
}