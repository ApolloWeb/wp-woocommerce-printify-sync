<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\StockSync;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class StockSyncService
{
    use TimeStampTrait;

    private PrintifyAPIClient $printifyClient;
    private LoggerInterface $logger;
    private $outOfStockVisibility;

    public function __construct(
        PrintifyAPIClient $printifyClient,
        LoggerInterface $logger
    ) {
        $this->printifyClient = $printifyClient;
        $this->logger = $logger;
        $this->outOfStockVisibility = get_option('woocommerce_hide_out_of_stock_items');
    }

    public function registerCronSchedule(): void
    {
        if (!wp_next_scheduled('printify_stock_sync')) {
            wp_schedule_event(time(), 'every_4_hours', 'printify_stock_sync');
        }

        add_action('printify_stock_sync', [$this, 'syncAllProductsStock']);
    }

    public function syncAllProductsStock(): void
    {
        try {
            $products = $this->getPrintifyProducts();
            $batchSize = 20;
            $batches = array_chunk($products, $batchSize);

            foreach ($batches as $batch) {
                $this->processBatch($batch);
                // Small delay between batches to prevent API rate limiting
                usleep(500000); // 0.5 second delay
            }

            $this->logger->info('Stock sync completed', [
                'products_count' => count($products),
                'timestamp' => $this->getCurrentTime()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Stock sync failed', [
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
        }
    }

    private function processBatch(array $products): void
    {
        foreach ($products as $product) {
            $this->syncProductStock($product);
        }
    }

    private function syncProductStock(array $product): void
    {
        try {
            $wcProduct = wc_get_product($product['wc_product_id']);
            if (!$wcProduct) {
                return;
            }

            // Get fresh stock data from Printify
            $printifyStock = $this->printifyClient->getProductStock($product['printify_id']);
            
            // Update main product stock status
            $inStock = false;
            foreach ($printifyStock['variants'] as $variant) {
                if ($variant['quantity'] > 0) {
                    $inStock = true;
                    break;
                }
            }

            // Update product visibility based on WooCommerce settings
            $this->updateProductVisibility($wcProduct, $inStock);

            // Update variant stock levels
            if ($wcProduct->is_type('variable')) {
                $this->updateVariantStock($wcProduct, $printifyStock['variants']);
            } else {
                $mainStock = $printifyStock['variants'][0]['quantity'] ?? 0;
                $wcProduct->set_stock_quantity($mainStock);
                $wcProduct->set_stock_status($mainStock > 0 ? 'instock' : 'outofstock');
            }

            $wcProduct->save();

            $this->logger->info('Product stock updated', [
                'product_id' => $wcProduct->get_id(),
                'timestamp' => $this->getCurrentTime()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to sync product stock', [
                'product_id' => $product['wc_product_id'],
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
        }
    }

    private function updateProductVisibility(\WC_Product $product, bool $inStock): void
    {
        // Only modify visibility if WooCommerce is set to hide out of stock items
        if ($this->outOfStockVisibility === 'yes' && !$inStock) {
            $product->set_catalog_visibility('hidden');
        } else {
            // Restore default visibility
            $product->set_catalog_visibility('visible');
        }
    }

    private function updateVariantStock(\WC_Product_Variable $product, array $printifyVariants): void
    {
        $variations = $product->get_available_variations();
        foreach ($variations as $variation) {
            $variationProduct = wc_get_product($variation['variation_id']);
            if (!$variationProduct) {
                continue;
            }

            // Match Printify variant with WooCommerce variation
            $printifyVariant = $this->matchPrintifyVariant($printifyVariants, $variation);
            if ($printifyVariant) {
                $variationProduct->set_stock_quantity($printifyVariant['quantity']);
                $variationProduct->set_stock_status($printifyVariant['quantity'] > 0 ? 'instock' : 'outofstock');
                $variationProduct->save();
            }
        }
    }

    private function matchPrintifyVariant(array $printifyVariants, array $wcVariation): ?array
    {
        foreach ($printifyVariants as $variant) {
            // Match based on SKU or variant options
            if ($variant['sku'] === $wcVariation['sku']) {
                return $variant;
            }
        }
        return null;
    }
}