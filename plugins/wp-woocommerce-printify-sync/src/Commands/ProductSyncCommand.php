<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Commands;

use ApolloWeb\WPWooCommercePrintifySync\Services\ProductSyncService;

class ProductSyncCommand
{
    private ProductSyncService $syncService;

    public function __construct(ProductSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Syncs a product from Printify to WooCommerce
     *
     * ## OPTIONS
     *
     * <printify_id>
     * : The Printify product ID to sync
     *
     * ## EXAMPLES
     *
     *     wp printify-sync product sync 123456
     *
     * @param array $args
     * @param array $assocArgs
     */
    public function sync(array $args, array $assocArgs): void
    {
        list($printifyId) = $args;

        try {
            $productId = $this->syncService->syncProduct($printifyId);
            \WP_CLI::success("Product synced successfully. WooCommerce ID: {$productId}");
        } catch (\Exception $e) {
            \WP_CLI::error($e->getMessage());
        }
    }
}