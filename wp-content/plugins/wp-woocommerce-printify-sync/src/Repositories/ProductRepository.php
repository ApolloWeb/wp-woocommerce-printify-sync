<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

class ProductRepository
{
    private string $currentTime;
    private string $currentUser;

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime; // 2025-03-15 18:13:12
        $this->currentUser = $currentUser; // ApolloWeb
    }

    public function getProductByPrintifyId(string $printifyId): ?\WC_Product
    {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_printify_product_id',
                    'value' => $printifyId,
                    'compare' => '='
                ]
            ]
        ];

        $products = wc_get_products($args);
        return !empty($products) ? $products[0] : null;
    }

    public function getProductBySku(string $sku): ?\WC_Product
    {
        return wc_get_product_id_by_sku($sku) ? wc_get_product_by_sku($sku) : null;
    }

    public function updateProductMeta(\WC_Product $product, array $printifyData): void
    {
        $product->update_meta_data('_printify_product_id', $printifyData['id']);
        $product->update_meta_data('_printify_provider_id', $printifyData['print_provider_id']);
        $product->update_meta_data('_printify_blueprint_id', $printifyData['blueprint_id']);
        $product->update_meta_data('_printify_shop_id', $printifyData['shop_id']);
        $product->update_meta_data('_printify_updated_at', $this->currentTime);
        $product->update_meta_data('_printify_updated_by', $this->currentUser);
        
        $product->save();
    }

    public function logProductSync(int $productId, array $printifyData, string $action = 'sync'): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_product_sync_log',
            [
                'product_id' => $productId,
                'printify_id' => $printifyData['id'],
                'provider_id' => $printifyData['print_provider_id'],
                'action' => $action,
                'sync_data' => json_encode($printifyData),
                'created_at' => $this->currentTime,
                'created_by' => $this->currentUser
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }
}