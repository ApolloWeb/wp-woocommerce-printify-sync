<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Storage;

use ApolloWeb\WPWooCommercePrintifySync\Storage\Interfaces\ProductDataInterface;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;

class WooCommerceStorage implements ProductDataInterface {

    public function getProductMeta(int $product_id, string $key): mixed {
        if ($this->isHPOSEnabled()) {
            return $this->getMetaDataHPOS($product_id, $key);
        }
        return get_post_meta($product_id, $key, true);
    }

    public function updateProductMeta(int $product_id, string $key, mixed $value): bool {
        if ($this->isHPOSEnabled()) {
            return $this->updateMetaDataHPOS($product_id, $key, $value);
        }
        return update_post_meta($product_id, $key, $value);
    }

    public function getPostsByType(string $type, array $args = []): array {
        if ($this->isHPOSEnabled() && $type === 'product_variation') {
            return $this->getVariationsHPOS($args);
        }
        return get_posts(array_merge(['post_type' => $type], $args));
    }

    public function updateProduct(array $data): int {
        if ($this->isHPOSEnabled()) {
            return $this->updateProductHPOS($data);
        }
        return wp_insert_post($data);
    }

    public function deleteProduct(int $product_id): bool {
        if ($this->isHPOSEnabled()) {
            return $this->deleteProductHPOS($product_id);
        }
        return (bool)wp_delete_post($product_id, true);
    }

    private function isHPOSEnabled(): bool {
        return class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore') 
            && wc_get_container()->get(OrdersTableDataStore::class)->custom_orders_table_usage_is_enabled();
    }

    private function getMetaDataHPOS(int $product_id, string $key) {
        $product = wc_get_product($product_id);
        return $product ? $product->get_meta($key) : null;
    }

    private function updateMetaDataHPOS(int $product_id, string $key, $value): bool {
        $product = wc_get_product($product_id);
        if (!$product) return false;
        
        $product->update_meta_data($key, $value);
        return (bool)$product->save();
    }

    private function getVariationsHPOS(array $args): array {
        $product = wc_get_product($args['post_parent'] ?? 0);
        if (!$product) return [];
        
        return array_map(
            fn($variation) => $variation->get_id(), 
            $product->get_children()
        );
    }

    private function updateProductHPOS(array $data): int {
        $product = new \WC_Product();
        $product->set_name($data['post_title']);
        $product->set_description($data['post_content']);
        $product->set_status($data['post_status']);
        
        if (!empty($data['ID'])) {
            $product->set_id($data['ID']);
        }
        
        return $product->save();
    }

    private function deleteProductHPOS(int $product_id): bool {
        $product = wc_get_product($product_id);
        return $product ? (bool)$product->delete(true) : false;
    }
}
