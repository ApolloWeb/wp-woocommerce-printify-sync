<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Repositories\ProductRepositoryInterface;

/**
 * Class ProductSyncService
 *
 * Service for synchronizing products.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */
class ProductSyncService implements ProductSyncServiceInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * ProductSyncService constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Sync products.
     */
    public function syncProducts(): void
    {
        $products = $this->productRepository->getProducts();

        foreach ($products as $product) {
            $this->productRepository->saveProduct($product);
        }
    }
}
