<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Products;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ProductSync
{
    private Client $client;
    private string $apiKey;
    private string $shopId;

    public function __construct()
    {
        $this->apiKey = get_option('wpwps_printify_key', '');
        $this->shopId = get_option('wpwps_shop_id', '');
        $this->client = new Client([
            'base_uri' => get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1/'),
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
            ]
        ]);
    }

    public function syncProduct(string $printifyId): ?int
    {
        try {
            $response = $this->client->get("shops/{$this->shopId}/products/{$printifyId}.json");
            $data = json_decode($response->getBody()->getContents(), true);
            
            $product = new Product([
                'title' => $data['title'],
                'description' => $data['description'],
                'price' => $data['price'],
                'variants' => $data['variants'],
                'images' => $data['images'],
                'printify_id' => $printifyId
            ]);

            return $this->createOrUpdateWooCommerceProduct($product);
        } catch (GuzzleException $e) {
            error_log("Printify Sync Error: " . $e->getMessage());
            return null;
        }
    }

    private function createOrUpdateWooCommerceProduct(Product $product): int
    {
        $existingId = $this->findExistingProduct($product->getPrintifyId());
        
        $data = $product->toWooCommerce();
        if ($existingId) {
            $data['ID'] = $existingId;
            wp_update_post($data);
            $productId = $existingId;
        } else {
            $productId = wp_insert_post($data);
        }

        if ($productId) {
            update_post_meta($productId, '_product_type', count($product->getVariants()) > 0 ? 'variable' : 'simple');
            $this->syncProductVariants($productId, $product->getVariants());
            $this->syncProductImages($productId, $product->getImages());
        }

        return $productId;
    }

    private function syncProductVariants(int $productId, array $variants): void
    {
        foreach ($variants as $variant) {
            $variationId = $this->findOrCreateVariation($productId, $variant);
            if ($variationId) {
                $data = $variant->toWooCommerceVariation();
                $data['ID'] = $variationId;
                wp_update_post($data);

                foreach ($variant->getAttributes() as $name => $value) {
                    update_post_meta($variationId, "attribute_" . sanitize_title($name), $value);
                }
            }
        }
    }

    private function findOrCreateVariation(int $productId, ProductVariant $variant): int
    {
        $existingId = $this->findExistingVariation($productId, $variant->getSku());
        if ($existingId) {
            return $existingId;
        }

        $data = $variant->toWooCommerceVariation();
        $data['post_parent'] = $productId;
        return wp_insert_post($data);
    }

    private function findExistingVariation(int $productId, string $sku): ?int
    {
        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_sku' AND meta_value = %s 
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_parent = %d AND post_type = 'product_variation'
            )",
            $sku,
            $productId
        );
        return (int)$wpdb->get_var($sql) ?: null;
    }

    private function syncProductImages(int $productId, array $images): void
    {
        $imageSync = new ImageSync();
        $imageSync->syncProductImages($productId, $images);
    }

    private function findExistingProduct(string $printifyId): ?int
    {
        $query = new \WP_Query([
            'post_type' => 'product',
            'meta_key' => '_printify_id',
            'meta_value' => $printifyId,
            'posts_per_page' => 1
        ]);

        return $query->have_posts() ? $query->posts[0]->ID : null;
    }

    public function handleWebhook(array $data): void
    {
        if (!isset($data['product_id'])) {
            return;
        }

        $this->syncProduct($data['product_id']);
    }
}