<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Services\BladeTemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyAPI;

class Products {
    private $template;
    private $printifyAPI;

    public function __construct(BladeTemplateEngine $template, PrintifyAPI $printifyAPI) {
        $this->template = $template;
        $this->printifyAPI = $printifyAPI;

        // Register AJAX handlers
        add_action('wp_ajax_wpwps_get_products', [$this, 'getProducts']);
        add_action('wp_ajax_wpwps_sync_product', [$this, 'syncProduct']);
        add_action('wp_ajax_wpwps_bulk_sync_products', [$this, 'bulkSyncProducts']);
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void {
        $data = [
            'products' => $this->getInitialProducts(),
            'sync_status' => get_option('wpwps_sync_status', 'idle'),
            'last_sync' => get_option('wpwps_last_sync_time'),
        ];

        echo $this->template->render('wpwps-products', $data);
    }

    public function enqueueAssets(string $hook): void {
        if ($hook !== 'printify-sync_page_wpwps-products') {
            return;
        }

        // Enqueue shared assets
        wp_enqueue_style('google-fonts-inter');
        wp_enqueue_style('bootstrap');
        wp_enqueue_script('bootstrap');
        wp_enqueue_style('font-awesome');
        wp_enqueue_script('wpwps-toast');

        // Our custom page assets
        wp_enqueue_style(
            'wpwps-products',
            WPWPS_URL . 'assets/css/wpwps-products.css',
            [],
            WPWPS_VERSION
        );
        wp_enqueue_script(
            'wpwps-products',
            WPWPS_URL . 'assets/js/wpwps-products.js',
            ['jquery', 'bootstrap', 'wpwps-toast'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-products', 'wpwps_products', [
            'nonce' => wp_create_nonce('wpwps_products_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'text' => [
                'confirm_sync' => __('Are you sure you want to sync this product?', 'wp-woocommerce-printify-sync'),
                'confirm_bulk_sync' => __('Are you sure you want to sync all selected products?', 'wp-woocommerce-printify-sync'),
            ]
        ]);
    }

    public function getProducts(): void {
        check_ajax_referer('wpwps_products_nonce', 'nonce');

        $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        $per_page = isset($_POST['per_page']) ? (int) $_POST['per_page'] : 10;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        try {
            $products = $this->printifyAPI->getProducts([
                'page' => $page,
                'per_page' => $per_page,
                'search' => $search,
            ]);

            wp_send_json_success($products);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    public function syncProduct(): void {
        check_ajax_referer('wpwps_products_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

        if (!$product_id) {
            wp_send_json_error([
                'message' => __('Invalid product ID', 'wp-woocommerce-printify-sync'),
                'code' => 400
            ]);
            return;
        }

        try {
            // Schedule product sync
            as_enqueue_async_action(
                'wpwps_process_product_sync',
                ['product_id' => $product_id],
                'product-sync'
            );

            wp_send_json_success([
                'message' => __('Product sync scheduled', 'wp-woocommerce-printify-sync')
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    public function bulkSyncProducts(): void {
        check_ajax_referer('wpwps_products_nonce', 'nonce');

        $product_ids = isset($_POST['product_ids']) ? array_map('intval', (array) $_POST['product_ids']) : [];

        if (empty($product_ids)) {
            wp_send_json_error([
                'message' => __('No products selected', 'wp-woocommerce-printify-sync'),
                'code' => 400
            ]);
            return;
        }

        try {
            foreach ($product_ids as $product_id) {
                as_enqueue_async_action(
                    'wpwps_process_product_sync',
                    ['product_id' => $product_id],
                    'product-sync'
                );
            }

            wp_send_json_success([
                'message' => sprintf(
                    __('Scheduled sync for %d products', 'wp-woocommerce-printify-sync'),
                    count($product_ids)
                )
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    private function getInitialProducts(): array {
        try {
            return $this->printifyAPI->getProducts([
                'page' => 1,
                'per_page' => 10
            ]);
        } catch (\Exception $e) {
            error_log('Failed to fetch initial products: ' . $e->getMessage());
            return [];
        }
    }
}