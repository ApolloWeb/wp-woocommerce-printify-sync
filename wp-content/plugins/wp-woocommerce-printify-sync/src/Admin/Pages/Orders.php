<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Services\BladeTemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyAPI;

class Orders {
    private $template;
    private $printifyAPI;

    public function __construct(BladeTemplateEngine $template, PrintifyAPI $printifyAPI) {
        $this->template = $template;
        $this->printifyAPI = $printifyAPI;

        // Register AJAX handlers
        add_action('wp_ajax_wpwps_get_orders', [$this, 'getOrders']);
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void {
        $data = [
            'orders' => $this->getInitialOrders(),
        ];

        $this->template->render('wpwps-orders', $data);
    }

    public function enqueueAssets(string $hook): void {
        if ($hook !== 'printify-sync_page_wpwps-orders') {
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
            'wpwps-orders',
            WPWPS_URL . 'assets/css/wpwps-orders.css',
            [],
            WPWPS_VERSION
        );
        wp_enqueue_script(
            'wpwps-orders',
            WPWPS_URL . 'assets/js/wpwps-orders.js',
            ['jquery', 'bootstrap', 'wpwps-toast'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-orders', 'wpwps_orders', [
            'nonce' => wp_create_nonce('wpwps_orders_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'text' => [
                'confirm_sync' => __('Are you sure you want to sync this order?', 'wp-woocommerce-printify-sync'),
                'confirm_bulk_sync' => __('Are you sure you want to sync all selected orders?', 'wp-woocommerce-printify-sync'),
            ]
        ]);
    }

    public function getOrders(): void {
        check_ajax_referer('wpwps_orders_nonce', 'nonce');

        $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        $per_page = isset($_POST['per_page']) ? (int) $_POST['per_page'] : 10;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $date_start = isset($_POST['date_start']) ? sanitize_text_field($_POST['date_start']) : '';
        $date_end = isset($_POST['date_end']) ? sanitize_text_field($_POST['date_end']) : '';

        try {
            $orders = $this->printifyAPI->getOrders([
                'page' => $page,
                'per_page' => $per_page,
                'search' => $search,
                'status' => $status,
                'date_start' => $date_start,
                'date_end' => $date_end
            ]);

            wp_send_json_success($orders);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    private function getInitialOrders(): array {
        try {
            return $this->printifyAPI->getOrders([
                'page' => 1,
                'per_page' => 10
            ]);
        } catch (\Exception $e) {
            error_log('Failed to fetch initial orders: ' . $e->getMessage());
            return [];
        }
    }
}