<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Page;

use ApolloWeb\WPWooCommercePrintifySync\Service\{
    PrintifyService,
    ProductService,
    ImageService
};

class ProductsPage extends AbstractAdminPage
{
    private PrintifyService $printifyService;
    private ProductService $productService;
    private ImageService $imageService;

    public function __construct(
        PrintifyService $printifyService,
        ProductService $productService,
        ImageService $imageService
    ) {
        $this->printifyService = $printifyService;
        $this->productService = $productService;
        $this->imageService = $imageService;
    }

    public function getTitle(): string
    {
        return __('Products', 'wp-woocommerce-printify-sync');
    }

    public function getMenuTitle(): string
    {
        return __('Products', 'wp-woocommerce-printify-sync');
    }

    public function getCapability(): string
    {
        return 'manage_woocommerce';
    }

    public function getMenuSlug(): string
    {
        return 'wpwps-products';
    }

    public function register(): void
    {
        add_submenu_page(
            'wpwps-dashboard',
            $this->getTitle(),
            $this->getMenuTitle(),
            $this->getCapability(),
            $this->getMenuSlug(),
            [$this, 'render']
        );

        // Register AJAX handlers
        add_action('wp_ajax_wpwps_import_product', [$this, 'handleProductImport']);
        add_action('wp_ajax_wpwps_sync_products', [$this, 'handleBulkSync']);
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_style(
            'wpwps-products',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/css/products.css',
            ['wpwps-admin-core'],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-products',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/js/products.js',
            ['jquery', 'wpwps-admin-core'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-products', 'wpwpsProducts', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_products'),
            'i18n' => [
                'importSuccess' => __('Product imported successfully!', 'wp-woocommerce-printify-sync'),
                'importError' => __('Failed to import product:', 'wp-woocommerce-printify-sync'),
                'syncSuccess' => __('Products synced successfully!', 'wp-woocommerce-printify-sync'),
                'syncError' => __('Failed to sync products:', 'wp-woocommerce-printify-sync'),
                'confirmSync' => __('Are you sure you want to sync all products?', 'wp-woocommerce-printify-sync')
            ]
        ]);
    }

    public function render(): void
    {
        if (!current_user_can($this->getCapability())) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $this->renderTemplate('products', [
            'products' => $this->productService->getProducts(),
            'printifyProducts' => $this->printifyService->getProducts(),
            'stats' => $this->productService->getStats(),
            'syncStatus' => $this->productService->getSyncStatus()
        ]);
    }

    public function handleProductImport(): void
    {
        check_ajax_referer('wpwps_products', 'nonce');

        if (!current_user_can($this->getCapability())) {
            wp_send_json_error(['message' => __('Permission denied.', 'wp-woocommerce-printify-sync')]);
        }

        $productId = sanitize_text_field($_POST['product_id'] ?? '');
        
        try {
            $result = $this->productService->importProduct($productId);
            wp_send_json_success([
                'message' => __('Product imported successfully!', 'wp-woocommerce-printify-sync'),
                'product' => $result
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function handleBulkSync(): void
    {
        check_ajax_referer('wpwps_products', 'nonce');

        if (!current_user_can($this->getCapability())) {
            wp_send_json_error(['message' => __('Permission denied.', 'wp-woocommerce-printify-sync')]);
        }

        try {
            $result = $this->productService->syncAll();
            wp_send_json_success([
                'message' => sprintf(
                    __('Successfully synced %d products!', 'wp-woocommerce-printify-sync'),
                    $result['count']
                ),
                'stats' => $result['stats']
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}