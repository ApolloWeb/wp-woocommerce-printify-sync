<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Models;

class ProductRepository
{
    private string $currentTime;
    private string $currentUser;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:43:33';
        $this->currentUser = 'ApolloWeb';
    }

    public function findByPrintifyId(string $printifyId): ?\WC_Product
    {
        $products = wc_get_products([
            'meta_key' => '_printify_id',
            'meta_value' => $printifyId,
            'limit' => 1
        ]);

        return !empty($products) ? $products[0] : null;
    }

    public function getAll(array $args = []): array
    {
        $defaultArgs = [
            'meta_key' => '_printify_id',
            'meta_compare' => 'EXISTS',
            'limit' => -1
        ];

        return wc_get_products(array_merge($defaultArgs, $args));
    }

    public function create(array $data): int
    {
        $product = new \WC_Product_Variable();
        
        $this->updateProductData($product, $data);
        
        return $product->save();
    }

    public function update(int $productId, array $data): int
    {
        $product = wc_get_product($productId);
        
        if (!$product) {
            throw new \Exception('Product not found');
        }

        $this->updateProductData($product, $data);
        
        return $product->save();
    }

    private function updateProductData(\WC_Product $product, array $data): void
    {
        $product->set_name($data['title']);
        $product->set_description($data['description']);
        $product->set_status('publish');

        // Set meta
        $product->update_meta_data('_printify_id', $data['id']);
        $product->update_meta_data('_printify_updated_at', $this->currentTime);
        $product->update_meta_data('_printify_updated_by', $this->currentUser);
    }
}