<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\PriceSync;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class PriceSyncService
{
    use TimeStampTrait;

    private PrintifyAPIClient $printifyClient;
    private LoggerInterface $logger;

    public function __construct(PrintifyAPIClient $printifyClient, LoggerInterface $logger)
    {
        $this->printifyClient = $printifyClient;
        $this->logger = $logger;
    }

    public function syncProductPrices(int $productId, array $printifyData): void
    {
        try {
            $product = wc_get_product($productId);
            if (!$product) {
                throw new \Exception('Product not found');
            }

            if ($product->is_type('variable')) {
                $this->syncVariableProductPrices($product, $printifyData);
            } else {
                $this->syncSimpleProductPrice($product, $printifyData);
            }

            $product->save();

        } catch (\Exception $e) {
            $this->logger->error('Price sync failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }

    private function syncVariableProductPrices(\WC_Product_Variable $product, array $printifyData): void
    {
        $variations = $product->get_available_variations();
        foreach ($variations as $variation) {
            $variationProduct = wc_get_product($variation['variation_id']);
            if (!$variationProduct) {
                continue;
            }

            $printifyVariant = $this->findMatchingPrintifyVariant($printifyData['variants'], $variation);
            if ($printifyVariant) {
                $this->updateProductPrice($variationProduct, $printifyVariant);
            }
        }
    }

    private function syncSimpleProductPrice(\WC_Product $product, array $printifyData): void
    {
        $printifyPrice = $printifyData['variants'][0]['price'] ?? null;
        if ($printifyPrice) {
            $this->updateProductPrice($product, $printifyData['variants'][0]);
        }
    }

    private function updateProductPrice(\WC_Product $product, array $printifyVariant): void
    {
        $basePrice = $printifyVariant['price'];
        $markup = get_option('printify_price_markup', 1.4); // 40% markup by default
        
        $regularPrice = $basePrice * $markup;
        $salePrice = $printifyVariant['sale_price'] ?? null;

        $product->set_regular_price($regularPrice);
        if ($salePrice) {
            $product->set_sale_price($salePrice * $markup);
        }
    }

    private function findMatchingPrintifyVariant(array $printifyVariants, array $wcVariation): ?array
    {
        foreach ($printifyVariants as $variant) {
            if ($variant['sku'] === $wcVariation['sku']) {
                return $variant;
            }
        }
        return null;
    }
}