<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

class OrdersPage
{
    public function register(): void
    {
        add_submenu_page(
            'wpwps-dashboard',
            __('Orders', 'wp-woocommerce-printify-sync'),
            __('Orders', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-orders',
            [$this, 'render']
        );

        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void
    {
        $orders = $this->getOrders();
        echo View::render('wpwps-orders', [
            'title' => __('Printify Orders', 'wp-woocommerce-printify-sync'),
            'orders' => $orders
        ]);
    }

    private function getOrders(): array
    {
        $orders = wc_get_orders([
            'meta_key' => '_printify_order_id',
            'meta_compare' => 'EXISTS',
            'limit' => 50
        ]);

        $formattedOrders = [];
        foreach ($orders as $order) {
            $formattedOrders[] = [
                'id' => $order->get_id(),
                'printify_id' => get_post_meta($order->get_id(), '_printify_order_id', true),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'date_created' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'tracking_number' => get_post_meta($order->get_id(), '_tracking_number', true),
                'tracking_url' => get_post_meta($order->get_id(), '_tracking_url', true)
            ];
        }

        return $formattedOrders;
    }

    public function enqueueAssets(): void
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpwps-orders') {
            return;
        }

        wp_enqueue_style('wpwps-orders', WPWPS_URL . 'assets/css/wpwps-orders.css', [], WPWPS_VERSION);
        wp_enqueue_script('wpwps-orders', WPWPS_URL . 'assets/js/wpwps-orders.js', ['jquery'], WPWPS_VERSION, true);
        wp_localize_script('wpwps-orders', 'wpwpsOrders', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-admin-nonce')
        ]);
    }
}