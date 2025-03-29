<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

class ProductsPage
{
    public function register(): void
    {
        add_submenu_page(
            'wpwps-dashboard',
            __('Products', 'wp-woocommerce-printify-sync'),
            __('Products', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-products',
            [$this, 'render']
        );

        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void
    {
        $products = $this->getProducts();
        echo View::render('wpwps-products', [
            'title' => __('Printify Products', 'wp-woocommerce-printify-sync'),
            'products' => $products
        ]);
    }

    private function getProducts(): array
    {
        $query = new \WP_Query([
            'post_type' => 'product',
            'meta_key' => '_printify_id',
            'meta_value' => '',
            'meta_compare' => '!=',
            'posts_per_page' => -1
        ]);

        $products = [];
        foreach ($query->posts as $post) {
            $products[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'printify_id' => get_post_meta($post->ID, '_printify_id', true),
                'price' => get_post_meta($post->ID, '_price', true),
                'status' => $post->post_status
            ];
        }

        return $products;
    }

    public function enqueueAssets(): void
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpwps-products') {
            return;
        }

        wp_enqueue_style('wpwps-products', WPWPS_URL . 'assets/css/wpwps-products.css', [], WPWPS_VERSION);
        wp_enqueue_script('wpwps-products', WPWPS_URL . 'assets/js/wpwps-products.js', ['jquery'], WPWPS_VERSION, true);
        wp_localize_script('wpwps-products', 'wpwpsProducts', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-admin-nonce')
        ]);
    }
}