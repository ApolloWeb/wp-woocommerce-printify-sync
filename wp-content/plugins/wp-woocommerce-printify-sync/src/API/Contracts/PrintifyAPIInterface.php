<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API\Contracts;

interface PrintifyAPIInterface {
    public function getShops(): array;
    public function getProducts(string $shop_id): array;
    public function createProduct(string $shop_id, array $product_data): array;
}
