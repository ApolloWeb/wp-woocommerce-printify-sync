<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Core\PrintifyClient;
use GuzzleHttp\Exception\GuzzleException;

class ProductProvider extends ServiceProvider
{
    private const OPTION_PREFIX = 'wpwps_';
    private PrintifyClient $client;
    private array $syncStats = [
        'created' => 0,
        'updated' => 0,
        'failed' => 0,
        'skipped' => 0
    ];

    public function boot(): void
    {
        $apiKey = get_option(self::OPTION_PREFIX . 'api_key', '');
        $apiEndpoint = get_option(self::OPTION_PREFIX . 'api_endpoint', 'https://api.printify.com/v1/');
        $this->client = new PrintifyClient($apiKey, 3, $apiEndpoint);

        $this->registerAdminMenu(
            'WC Printify Products',
            'Products',
            'manage_woocommerce',
            'wpwps-products',
            [$this, 'renderProductsPage']
        );

        $this->registerAjaxEndpoint('wpwps_sync_products', [$this, 'syncProducts']);
        $this->registerAjaxEndpoint('wpwps_get_sync_status', [$this, 'getSyncStatus']);

        add_action('wp_ajax_wpwps_sync_single_product', [$this, 'syncSingleProduct']);
    }

    public function renderProductsPage(): void
    {
        $data = [
            'total_products' => $this->getTotalProducts(),
            'synced_products' => $this->getSyncedProductsCount(),
            'last_sync' => get_option(self::OPTION_PREFIX . 'last_product_sync'),
            'sync_stats' => $this->syncStats
        ];

        echo $this->view->render('wpwps-products', $data);
    }

    public function syncProducts(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        try {
            $shopId = get_option(self::OPTION_PREFIX . 'shop_id');
            $response = $this->client->get("shops/{$shopId}/products.json");
            $products = json_decode($response->getBody()->getContents(), true);

            foreach ($products as $product) {
                $this->createOrUpdateProduct($product);
            }

            update_option(self::OPTION_PREFIX . 'last_product_sync', current_time('mysql'));
            wp_send_json_success([
                'message' => 'Products synchronized successfully',
                'stats' => $this->syncStats
            ]);
        } catch (GuzzleException $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function syncSingleProduct(int $printifyId): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        try {
            $shopId = get_option(self::OPTION_PREFIX . 'shop_id');
            $response = $this->client->get("shops/{$shopId}/products/{$printifyId}.json");
            $product = json_decode($response->getBody()->getContents(), true);

            $result = $this->createOrUpdateProduct($product);
            wp_send_json_success([
                'message' => $result ? 'Product synchronized successfully' : 'Product sync failed',
                'product' => $result
            ]);
        } catch (GuzzleException $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function createOrUpdateProduct(array $printifyProduct): ?int
    {
        $existingProduct = $this->findExistingProduct($printifyProduct['id']);

        $productData = [
            'name' => $printifyProduct['title'],
            'type' => 'variable',
            'description' => $printifyProduct['description'],
            'short_description' => $printifyProduct['description'],
            'status' => 'publish',
            'catalog_visibility' => 'visible',
            'meta_data' => [
                [
                    'key' => '_printify_product_id',
                    'value' => $printifyProduct['id']
                ],
                [
                    'key' => '_printify_blueprint_id',
                    'value' => $printifyProduct['blueprint_id']
                ],
                [
                    'key' => '_printify_shop_id',
                    'value' => $printifyProduct['shop_id']
                ],
                [
                    'key' => '_printify_is_synced',
                    'value' => true
                ]
            ]
        ];

        try {
            if ($existingProduct) {
                $productId = wp_update_post([
                    'ID' => $existingProduct,
                    'post_title' => $productData['name'],
                    'post_content' => $productData['description'],
                    'post_excerpt' => $productData['short_description']
                ]);
                $this->syncStats['updated']++;
            } else {
                $productId = wp_insert_post([
                    'post_title' => $productData['name'],
                    'post_content' => $productData['description'],
                    'post_excerpt' => $productData['short_description'],
                    'post_type' => 'product',
                    'post_status' => 'publish'
                ]);
                $this->syncStats['created']++;
            }

            if (is_wp_error($productId)) {
                $this->syncStats['failed']++;
                return null;
            }

            $this->updateProductMeta($productId, $productData['meta_data']);
            $this->syncProductVariants($productId, $printifyProduct['variants']);
            $this->syncProductImages($productId, $printifyProduct['images']);

            return $productId;
        } catch (\Exception $e) {
            $this->syncStats['failed']++;
            error_log('WPWPS Product Sync Error: ' . $e->getMessage());
            return null;
        }
    }

    private function findExistingProduct(int $printifyId): ?int
    {
        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' 
            AND meta_value = %s 
            LIMIT 1",
            $printifyId
        );
        return (int) $wpdb->get_var($sql) ?: null;
    }

    private function updateProductMeta(int $productId, array $metadata): void
    {
        foreach ($metadata as $meta) {
            update_post_meta($productId, $meta['key'], $meta['value']);
        }
    }

    private function syncProductVariants(int $productId, array $variants): void
    {
        // Create attributes
        $attributes = $this->extractAttributes($variants);
        $this->createProductAttributes($productId, $attributes);

        // Create variations
        foreach ($variants as $variant) {
            $this->createProductVariation($productId, $variant);
        }
    }

    private function extractAttributes(array $variants): array
    {
        $attributes = [];
        foreach ($variants as $variant) {
            foreach ($variant['options'] as $name => $value) {
                if (!isset($attributes[$name])) {
                    $attributes[$name] = [];
                }
                $attributes[$name][] = $value;
            }
        }

        // Deduplicate values
        foreach ($attributes as &$values) {
            $values = array_unique($values);
        }

        return $attributes;
    }

    private function createProductAttributes(int $productId, array $attributes): void
    {
        $productAttributes = [];
        foreach ($attributes as $name => $values) {
            $attributeName = wc_sanitize_taxonomy_name('pa_' . $name);
            $productAttributes[$attributeName] = [
                'name' => $attributeName,
                'value' => '',
                'position' => array_search($name, array_keys($attributes)),
                'is_visible' => 1,
                'is_variation' => 1,
                'is_taxonomy' => 1
            ];

            // Create attribute terms if they don't exist
            foreach ($values as $value) {
                wp_insert_term($value, $attributeName);
            }
        }

        update_post_meta($productId, '_product_attributes', $productAttributes);
    }

    private function createProductVariation(int $productId, array $variant): void
    {
        $variation = [
            'post_title' => "Product #{$productId} Variation",
            'post_name' => "product-{$productId}-variation",
            'post_status' => 'publish',
            'post_parent' => $productId,
            'post_type' => 'product_variation',
            'guid' => ''
        ];

        $variationId = wp_insert_post($variation);

        if (!is_wp_error($variationId)) {
            // Set variant meta
            update_post_meta($variationId, '_regular_price', $variant['price']);
            update_post_meta($variationId, '_price', $variant['price']);
            update_post_meta($variationId, '_printify_variant_id', $variant['id']);
            update_post_meta($variationId, '_printify_cost', $variant['cost']);
            update_post_meta($variationId, '_sku', $variant['sku']);

            // Set variant attributes
            foreach ($variant['options'] as $name => $value) {
                $attributeName = wc_sanitize_taxonomy_name('pa_' . $name);
                update_post_meta($variationId, "attribute_{$attributeName}", $value);
            }
        }
    }

    private function syncProductImages(int $productId, array $images): void
    {
        foreach ($images as $index => $image) {
            $attachment_id = $this->uploadImage($image['src'], $productId);
            if ($attachment_id) {
                if ($index === 0) {
                    set_post_thumbnail($productId, $attachment_id);
                }
                update_post_meta($attachment_id, '_printify_image_id', $image['id']);
            }
        }
    }

    private function uploadImage(string $url, int $productId): ?int
    {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return null;
        }

        $file_array = [
            'name' => basename($url),
            'tmp_name' => $tmp
        ];

        $attachmentId = media_handle_sideload($file_array, $productId);
        @unlink($tmp);

        return is_wp_error($attachmentId) ? null : $attachmentId;
    }

    private function getApiKey(): string
    {
        return get_option(self::OPTION_PREFIX . 'api_key', '');
    }

    private function getTotalProducts(): int
    {
        try {
            $shopId = get_option(self::OPTION_PREFIX . 'shop_id');
            $response = $this->client->get("shops/{$shopId}/products.json");
            $products = json_decode($response->getBody()->getContents(), true);
            return count($products);
        } catch (GuzzleException $e) {
            return 0;
        }
    }

    private function getSyncedProductsCount(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_is_synced' 
            AND meta_value = '1'"
        );
    }
}