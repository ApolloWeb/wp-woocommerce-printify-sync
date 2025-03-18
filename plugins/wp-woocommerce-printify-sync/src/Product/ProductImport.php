<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Product;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyApi;

class ProductImport {
    protected PrintifyApi $api;

    public function __construct(PrintifyApi $api) {
        $this->api = $api;
    }

    public function importProducts(): array {
        $shops = $this->api->getShops();
        // ...existing processing code...
        return ['imported' => count($shops), 'shops' => $shops];
    }
}
