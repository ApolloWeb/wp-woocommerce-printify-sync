<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\APIInterface;
use ApolloWeb\WPWooCommercePrintifySync\Models\Product;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\ResponseHelper;

class ProductService
{
    private APIInterface $api;
    private ImageService $imageService;

    public function __construct(APIInterface $api, ImageService $imageService)
    {
        $this->api = $api;
        $this->imageService = $imageService;
    }

    public function importProducts(): array
    {
        try {
            $printifyProducts = $this->api->get('/shops/{shop_id}/products.json');
            $importedProducts = [];

            foreach ($printifyProducts as $productData) {
                $product = new Product($productData);
                $wcProductId = $this->createOrUpdateWooCommerceProduct($product);
                
                if ($wcProductId) {
                    $this->imageService->importProductImages($wcProductId, $product->getImages());
                    $importedProducts[] = $wcProductId;
                }
            }

            return ResponseHelper::success(
                ['products' => $importedProducts],
                sprintf('Successfully imported %d products', count($importedProducts))
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    private function createOrUpdateWooCommerceProduct(Product $product): int
    {
        $existingProductId = $this->getExistingProductId($product->getId());

        if ($existingProductId) {
            $productData = $product->toWooCommerceProduct();
            $productData['ID'] = $existingProductId;
            return wp_update_post($productData);
        }

        return wp_insert_post($product->toWooCommerceProduct());
    }

    private function getExistingProductId(int $printifyId): ?int
    {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' 
            AND meta_value = %d",
            $printifyId
        ));

        return $result ? (int) $result : null;
    }
}