<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

use ApolloWeb\WPWooCommercePrintifySync\Services\OrderSyncServiceInterface;

/**
 * Class OrderProcessor
 *
 * Manages order synchronization.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Orders
 */
class OrderProcessor
{
    /**
     * @var OrderSyncServiceInterface
     */
    protected $orderSyncService;

    /**
     * OrderProcessor constructor.
     *
     * @param OrderSyncServiceInterface $orderSyncService
     */
    public function __construct(OrderSyncServiceInterface $orderSyncService)
    {
        $this->orderSyncService = $orderSyncService;
    }

    /**
     * Sync orders.
     */
    public function syncOrders(): void
    {
        $this->orderSyncService->syncOrders();
    }
}
