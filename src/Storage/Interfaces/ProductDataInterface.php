<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Storage\Interfaces;

interface ProductDataInterface {
    public function getProductMeta(int $product_id, string $key): mixed;
    public function updateProductMeta(int $product_id, string $key, mixed $value): bool;
    public function getPostsByType(string $type, array $args = []): array;
    public function updateProduct(array $data): int;
    public function deleteProduct(int $product_id): bool;
}
