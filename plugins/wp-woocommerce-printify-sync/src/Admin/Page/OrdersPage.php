<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Page;

use ApolloWeb\WPWooCommercePrintifySync\Service\{
    OrderService,
    PrintifyService,
    ShippingService
};

class OrdersPage extends AbstractAdminPage
{
    private OrderService $orderService;
    private PrintifyService $printifyService;
    private ShippingService $shippingService;

    public function __construct(
        OrderService $orderService,
        PrintifyService $printifyService,
        ShippingService $shippingService
    ) {
        $this->orderService = $orderService;
        $this->printifyService = $printifyService;
        $this->shippingService = $shippingService;
    }

    public function getTitle(): string
    {
        return __('Orders', 'wp-woocommerce-printify-sync');
    }

    public function getMenuTitle(): string
    {
        return sprintf(
            __('Orders %s', 'wp-woocommerce-printify-sync'),
            '<span class="awaiting-mod">' . $this->orderService->getPendingCount() . '</span>'
        );
    }

    public function getCapability(): string
    {
        return 'manage_woocommerce';
    }

    public function getMenuSlug(): string
    {
        return 'wpwps-orders';
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_style(
            'wpwps-orders',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/css/orders.css',
            ['wpwps-admin-core'],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-orders',
            plugin_dir_url(WPWPS_PLUGIN_FILE) . 'assets/js/orders.js',
            ['jquery', 'wpwps-admin-core'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-orders', 'wpwpsOrders', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-orders'),
            'i18n' => [
                'confirmCancel' => __('Are you sure you want to cancel this order?', 'wp-woocommerce-printify-sync'),
                'confirmRefund' => __('Are you sure you want to refund this order?', 'wp-woocommerce-printify-sync'),
                'processing' => __('Processing...', 'wp-woocommerce-printify-sync'),
                'success' => __('Success!', 'wp-woocommerce-printify-sync'),
                'error' => __('Error:', 'wp-woocommerce-printify-sync')
            ]
        ]);
    }

    public function render(): void
    {
        if (!current_user_can($this->getCapability())) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $currentStatus = sanitize_text_field($_GET['status'] ?? 'all');
        $currentPage = max(1, (int)($_GET['paged'] ?? 1));
        $search = sanitize_text_field($_GET['s'] ?? '');

        $ordersList = $this->orderService->getOrders([
            'status' => $currentStatus,
            'page' => $currentPage,
            'search' => $search,
            'per_page' => 20
        ]);

        $this->renderTemplate('orders', [
            'orders' => $ordersList['orders'],
            'pagination' => $ordersList['pagination'],
            'stats' => $this->orderService->getStats(),
            'statuses' => $this->orderService->getStatuses(),
            'currentStatus' => $currentStatus,
            'search' => $search
        ]);
    }

    public function registerAjaxHandlers(): void
    {
        add_action('wp_ajax_wpwps_sync_order', [$this, 'handleOrderSync']);
        add_action('wp_ajax_wpwps_cancel_order', [$this, 'handleOrderCancel']);
        add_action('wp_ajax_wpwps_refund_order', [$this, 'handleOrderRefund']);
        add_action('wp_ajax_wpwps_get_tracking', [$this, 'handleGetTracking']);
    }

    public function handleOrderSync(): void
    {
        check_ajax_referer('wpwps-orders', 'nonce');
        
        if (!current_user_can($this->getCapability())) {
            wp_send_json_error(['message' => __('Permission denied.', 'wp-woocommerce-printify-sync')]);
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        
        try {
            $result = $this->orderService->syncOrder($orderId);
            wp_send_json_success([
                'message' => __('Order synced successfully!', 'wp-woocommerce-printify-sync'),
                'order' => $result
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // Similar handlers for cancel, refund, and tracking...
}