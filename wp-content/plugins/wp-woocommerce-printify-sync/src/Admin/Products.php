<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\API\Contracts\PrintifyAPIInterface;

class Products {
    private PrintifyAPIInterface $api;

    public function __construct(PrintifyAPIInterface $api) {
        $this->api = $api;
    }

    public function init(): void {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_get_printify_products', [$this, 'handleGetProducts']);
    }

    public function renderPage(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        require PRINTIFY_SYNC_PATH . 'templates/admin/products.php';
    }

    public function enqueueAssets(string $hook): void {
        if ($hook !== 'printify_page_printify-sync-products') {
            return;
        }

        // Enqueue main admin styles and bootstrap
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
            [],
            '5.1.3'
        );

        wp_enqueue_style(
            'fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            [],
            '6.0.0'
        );

        wp_enqueue_style('printify-sync-admin');

        // Enqueue script
        wp_enqueue_script(
            'printify-sync-products',
            PRINTIFY_SYNC_URL . 'assets/js/products.js',
            ['jquery'],
            PRINTIFY_SYNC_VERSION,
            true
        );

        wp_localize_script('printify-sync-products', 'printifyProducts', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('printify_products'),
            'shopId' => get_option('printify_shop_id', ''),
            'i18n' => [
                'loading' => __('Loading products...', 'wp-woocommerce-printify-sync'),
                'loadError' => __('Error loading products', 'wp-woocommerce-printify-sync'),
                'noProducts' => __('No products found', 'wp-woocommerce-printify-sync'),
            ]
        ]);
    }

    public function handleGetProducts(): void {
        try {
            check_ajax_referer('printify_products', 'security');

            $shop_id = isset($_POST['shop_id']) ? sanitize_text_field($_POST['shop_id']) : '';
            if (empty($shop_id)) {
                throw new \Exception(__('No shop ID provided', 'wp-woocommerce-printify-sync'));
            }

            error_log('Getting products for shop: ' . $shop_id);
            $products = $this->api->getProducts($shop_id);
            
            error_log('Products response: ' . print_r($products, true));
            
            if (empty($products)) {
                wp_send_json_success(['products' => [], 'message' => __('No products found', 'wp-woocommerce-printify-sync')]);
                return;
            }

            // Format products for display
            $formatted_products = array_map(function($product) {
                return [
                    'id' => $product['id'],
                    'title' => $product['title'],
                    'status' => isset($product['visible']) ? ($product['visible'] ? 'published' : 'draft') : 'draft'
                ];
            }, $products);

            wp_send_json_success(['products' => $formatted_products]);

        } catch (\Exception $e) {
            error_log('Printify products error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
