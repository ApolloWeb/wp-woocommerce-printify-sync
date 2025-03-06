<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class ApiManager {

    private $printifyApiKey;
    private $woocommerceApiKey;

    public function __construct($printifyApiKey, $woocommerceApiKey) {
        $this->printifyApiKey = $printifyApiKey;
        $this->woocommerceApiKey = $woocommerceApiKey;
    }

    public function getEndpoint() {
        $mode = get_option('environment_mode', 'production');
        if ($mode === 'development') {
            return 'https://api-dev.printify.com/v1';
        }
        return 'https://api.printify.com/v1';
    }

    public function getPrintifyProducts() {
        $endpoint = $this->getEndpoint() . '/shops/.json';
        // Logic to get products from Printify API
    }

    public function syncToWooCommerce($products) {
        // Logic to sync products to WooCommerce
    }

    // Other API related functions
}