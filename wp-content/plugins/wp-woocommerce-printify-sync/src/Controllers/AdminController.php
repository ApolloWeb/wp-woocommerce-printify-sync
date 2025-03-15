<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Controllers;

class AdminController
{
    private $productService;

    public function __construct($productService)
    {
        $this->productService = $productService;
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_import_printify_products', [$this, 'handleProductImport']);
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_script(
            'printify-product-import',
            plugin_dir_url(__FILE__) . '../Assets/js/product-import.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('printify-product-import', 'printifyAjax', [
            'nonce' => wp_create_nonce('printify_import_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }

    public function handleProductImport(): void
    {
        check_ajax_referer('printify_import_nonce');

        try {
            $importedProducts = $this->productService->importProducts();
            wp_send_json_success([
                'message' => sprintf('Successfully imported %d products', count($importedProducts)),
                'products' => $importedProducts
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}