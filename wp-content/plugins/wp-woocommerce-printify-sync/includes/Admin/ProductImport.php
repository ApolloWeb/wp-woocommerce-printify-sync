<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ProductImport {
    // ...existing code...

    public function render_import_page() {
        $products = [];
        $error = '';

        try {
            $result = $this->product_service->get_products(1, 20);
            if (!is_wp_error($result)) {
                $products = $result['data'] ?? [];
            } else {
                $error = $result->get_error_message();
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        require_once WWPS_PATH . 'templates/product-import-page.php';
    }

    // ...existing code...
}
