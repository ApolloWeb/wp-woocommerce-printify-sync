<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Products;

use ApolloWeb\WPWooCommercePrintifySync\Services\ProductSyncServiceInterface;

/**
 * Class ProductManager
 *
 * Manages product synchronization.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Products
 */
class ProductManager
{
    /**
     * @var ProductSyncServiceInterface
     */
    protected $productSyncService;

    /**
     * ProductManager constructor.
     *
     * @param ProductSyncServiceInterface $productSyncService
     */
    public function __construct(ProductSyncServiceInterface $productSyncService)
    {
        $this->productSyncService = $productSyncService;
    }

    /**
     * Sync products.
     */
    public function syncProducts(): void
    {
        $this->productSyncService->syncProducts();
    }
}
