<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;

class DashboardProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerAdminMenu(
            __('Printify Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard']
        );

        $this->registerAjaxEndpoint('wpwps_get_sync_status', [$this, 'getSyncStatus']);
        $this->registerAjaxEndpoint('wpwps_get_sales_data', [$this, 'getSalesData']);
    }

    public function renderDashboard(): void
    {
        $data = [
            'sync_status' => $this->getSyncStats(),
            'api_status' => $this->getApiStatus(),
            'email_queue' => $this->getEmailQueueStatus(),
            'recent_orders' => $this->getRecentOrders(),
        ];

        echo $this->view->render('wpwps-dashboard', $data);
    }

    private function getSyncStats(): array
    {
        global $wpdb;

        $total_products = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_printify_product_id'
            )
        );

        $synced_products = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
                '_printify_is_synced',
                '1'
            )
        );

        return [
            'total_products' => (int) $total_products,
            'synced_products' => (int) $synced_products,
            'last_sync' => get_option('wpwps_last_sync_time'),
        ];
    }

    private function getApiStatus(): array
    {
        $settings = get_option('wpwps_settings');
        $api_key = $settings['api_key'] ?? '';

        if (empty($api_key)) {
            return [
                'status' => 'error',
                'message' => __('API key not configured', 'wp-woocommerce-printify-sync')
            ];
        }

        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => 'https://api.printify.com/v1/',
                'headers' => [
                    'Authorization' => "Bearer {$api_key}",
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 5
            ]);

            $response = $client->get('shops.json');
            
            return [
                'status' => 'success',
                'message' => __('API connection healthy', 'wp-woocommerce-printify-sync')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function getEmailQueueStatus(): array
    {
        return [
            'queued' => wp_count_posts('wpwps_email_queue')->draft ?? 0,
            'sent_today' => wp_count_posts('wpwps_email_queue')->publish ?? 0,
            'failed' => wp_count_posts('wpwps_email_queue')->trash ?? 0
        ];
    }

    private function getRecentOrders(): array
    {
        $orders = wc_get_orders([
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => '_printify_order_id',
            'meta_compare' => 'EXISTS'
        ]);

        $formatted_orders = [];
        foreach ($orders as $order) {
            $formatted_orders[] = [
                'id' => $order->get_id(),
                'printify_id' => $order->get_meta('_printify_order_id'),
                'status' => $order->get_meta('_printify_order_status'),
                'total' => $order->get_total(),
                'date' => $order->get_date_created()->format('Y-m-d H:i:s')
            ];
        }

        return $formatted_orders;
    }

    public function getSyncStatus(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        wp_send_json_success($this->getSyncStats());
    }

    public function getSalesData(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        $days = isset($_GET['days']) ? absint($_GET['days']) : 30;
        $end_date = new \DateTime();
        $start_date = (new \DateTime())->modify("-{$days} days");

        $data = [
            'labels' => [],
            'sales' => [],
            'orders' => []
        ];

        for ($i = 0; $i < $days; $i++) {
            $date = $start_date->format('Y-m-d');
            $data['labels'][] = $date;

            $orders = wc_get_orders([
                'date_created' => '>=' . $date . ' 00:00:00',
                'date_created' => '<=' . $date . ' 23:59:59',
                'meta_key' => '_printify_order_id',
                'meta_compare' => 'EXISTS'
            ]);

            $data['orders'][] = count($orders);
            $data['sales'][] = array_reduce($orders, function($carry, $order) {
                return $carry + $order->get_total();
            }, 0);

            $start_date->modify('+1 day');
        }

        wp_send_json_success($data);
    }
}