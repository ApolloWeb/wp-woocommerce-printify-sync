<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class FulfillmentDashboard
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenuItems']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_get_fulfillment_stats', [$this, 'getFulfillmentStats']);
        add_action('wp_ajax_wpwps_get_recent_orders', [$this, 'getRecentOrders']);
    }

    public function addMenuItems(): void
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-synchronization'
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Fulfillment Status', 'wp-woocommerce-printify-sync'),
            __('Fulfillment Status', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-fulfillment',
            [$this, 'renderFulfillmentStatus']
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if (!in_array($hook, ['toplevel_page_wpwps-dashboard', 'printify-sync_page_wpwps-fulfillment'])) {
            return;
        }

        wp_enqueue_style('wpwps-admin', plugins_url('assets/css/admin.css', WPWPS_PLUGIN_FILE));
        wp_enqueue_script(
            'wpwps-admin',
            plugins_url('assets/js/admin.js', WPWPS_PLUGIN_FILE),
            ['jquery', 'wp-util'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-admin', 'wpwpsAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_admin'),
        ]);
    }

    public function renderDashboard(): void
    {
        include dirname(WPWPS_PLUGIN_FILE) . '/templates/admin/dashboard.php';
    }

    public function renderFulfillmentStatus(): void
    {
        include dirname(WPWPS_PLUGIN_FILE) . '/templates/admin/fulfillment-status.php';
    }

    public function getFulfillmentStats(): void
    {
        check_ajax_referer('wpwps_admin');

        global $wpdb;

        $stats = [
            'pending' => $this->getStatusCount('submitted'),
            'in_production' => $this->getStatusCount('in_production'),
            'completed' => $this->getStatusCount('completed'),
            'failed' => $this->getStatusCount('failed'),
            'recent_failures' => $this->getRecentFailures(),
        ];

        wp_send_json_success($stats);
    }

    public function getRecentOrders(): void
    {
        check_ajax_referer('wpwps_admin');

        global $wpdb;

        $orders = $wpdb->get_results(
            "SELECT ft.*, o.post_status as order_status 
            FROM {$wpdb->prefix}wpwps_fulfillment_tracking ft 
            LEFT JOIN {$wpdb->posts} o ON ft.order_id = o.ID 
            ORDER BY ft.created_at DESC 
            LIMIT 10"
        );

        $formatted_orders = array_map(function($order) {
            return [
                'order_id' => $order->order_id,
                'order_number' => '#' . $order->order_id,
                'status' => $order->status,
                'printify_id' => $order->printify_id,
                'created_at' => human_time_diff(strtotime($order->created_at)),
                'updated_at' => human_time_diff(strtotime($order->updated_at)),
                'details' => json_decode($order->details, true),
            ];
        }, $orders);

        wp_send_json_success($formatted_orders);
    }

    private function getStatusCount(string $status): int
    {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_fulfillment_tracking 
            WHERE status = %s",
            $status
        ));
    }

    private function getRecentFailures(): array
    {
        global $wpdb;

        $failures = $wpdb->get_results(
            "SELECT order_id, details, created_at 
            FROM {$wpdb->prefix}wpwps_fulfillment_tracking 
            WHERE status = 'failed' 
            ORDER BY created_at DESC 
            LIMIT 5"
        );

        return array_map(function($failure) {
            $details = json_decode($failure->details, true);
            return [
                'order_id' => $failure->order_id,
                'error' => $details['error'] ?? 'Unknown error',
                'date' => human_time_diff(strtotime($failure->created_at)),
            ];
        }, $failures);
    }
}