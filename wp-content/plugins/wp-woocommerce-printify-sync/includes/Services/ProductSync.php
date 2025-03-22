<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ProductSync {
    private $api;
    private $logger;

    public function __construct(PrintifyApi $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
    }

    public function syncProduct(int $printify_id): bool {
        try {
            $product_data = $this->api->request("products/{$printify_id}.json");
            // Product sync logic will go here
            $this->logger->log("Synced product {$printify_id}");
            return true;
        } catch (\Exception $e) {
            $this->logger->log($e->getMessage(), 'error');
            return false;
        }
    }
}
