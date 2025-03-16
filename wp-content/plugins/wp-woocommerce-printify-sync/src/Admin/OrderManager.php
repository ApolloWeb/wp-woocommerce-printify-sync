<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Services\OrderService;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class OrderManager
{
    use TimeStampTrait;

    private OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenuItems']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_create_printify_order', [$this, 'handleCreateOrder']);
        add_action('wp_ajax_wpwps_cancel_printify_order', [$this, 'handleCancelOrder']);
    }

    public function addMenuItems(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Printify Orders', 'wp-woocommerce-printify-sync'),
            __('Printify Orders', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-orders',
            [$this, 'renderOrdersPage']
        );
    }

    public function enqueueAssets(): void
    {
        $screen = get_current_screen();
        if ($screen->id !== 'woocommerce_page_wpwps-orders') {
            return;
        }

        wp_enqueue_style(
            'wpwps-admin',
            plugins_url('assets/css/admin.css', WPWPS_PLUGIN_FILE),
            [],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-orders',
            plugins_url('assets/js/orders.js', WPWPS_PLUGIN_FILE),
            ['jquery'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-orders', 'wpwpsOrders', [
            'nonce' => wp_create_nonce('wpwps-orders'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'i18n' => [
                'confirm_cancel' => __('Are you sure you want to cancel this order?', 'wp-woocommerce-printify-sync'),
                'error_create' => __('Failed to create Printify order', 'wp-woocommerce-printify-sync'),
                'error_cancel' => __('Failed to cancel Printify order', 'wp-woocommerce-printify-sync')
            ]
        ]);
    }

    public function renderOrdersPage(): void
    {
        include WPWPS_PLUGIN_DIR . 'templates/admin/orders.php';
    }

    public function handleCreateOrder(): void
    {
        check_ajax_referer('wpwps-orders');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        if (!$orderId) {
            wp_send_json_error('Invalid order ID');
        }

        try {
            $order = wc_get_order($orderId);
            if (!$order) {
                throw new \Exception('Order not found');
            }

            $result = $this->orderService->createPrintifyOrder($order);
            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function handleCancelOrder(): void
    {
        check_ajax_referer('wpwps-orders');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
        }

        $printifyOrderId = sanitize_text_field($_POST['printify_order_id'] ?? '');
        if (!$printifyOrderId) {
            wp_send_json_error('Invalid Printify order ID');
        }

        try {
            $this->orderService->cancelOrder($printifyOrderId);
            wp_send_json_success();
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}