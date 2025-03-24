<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Container;
use ApolloWeb\WPWooCommercePrintifySync\View\BladeTemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Services\EmailService;
use ApolloWeb\WPWooCommercePrintifySync\Services\SyncService;
use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApi;

class Dashboard
{
    private BladeTemplateEngine $templateEngine;
    private EmailService $emailService;
    private SyncService $syncService;
    private PrintifyApi $printifyApi;

    public function __construct(Container $container)
    {
        $this->templateEngine = $container->get(BladeTemplateEngine::class);
        $this->emailService = $container->get(EmailService::class);
        $this->syncService = $container->get(SyncService::class);
        $this->printifyApi = $container->get(PrintifyApi::class);

        // Register AJAX handlers
        add_action('wp_ajax_wpwps_get_dashboard_data', [$this, 'getDashboardData']);
        
        // Add dashboard widgets
        add_action('wp_dashboard_setup', [$this, 'addDashboardWidgets']);
    }

    public function render(): void
    {
        $data = $this->getDashboardData();
        echo $this->templateEngine->render('admin.dashboard', $data);
    }

    public function getDashboardData(): array
    {
        return [
            'email_queue' => [
                'pending' => $this->emailService->getPendingEmailCount(),
                'failed' => $this->emailService->getFailedEmailCount(),
                'processed' => $this->emailService->getProcessedCount24Hours(),
            ],
            'sync_status' => [
                'products' => $this->syncService->getProductSyncStatus(),
                'orders' => $this->syncService->getOrderSyncStatus(),
                'last_sync' => $this->syncService->getLastSyncTime(),
                'next_sync' => $this->syncService->getNextScheduledSync(),
            ],
            'api_health' => [
                'printify' => $this->printifyApi->checkApiHealth(),
                'rate_limits' => $this->printifyApi->getRateLimits(),
                'webhook_status' => $this->syncService->getWebhookStatus(),
            ],
            'sales_data' => $this->getSalesData(),
            'recent_activity' => $this->getRecentActivity(),
        ];
    }

    public function addDashboardWidgets(): void
    {
        wp_add_dashboard_widget(
            'wpwps_email_queue_widget',
            __('Printify Sync - Email Queue', 'wp-woocommerce-printify-sync'),
            [$this, 'renderEmailQueueWidget']
        );

        wp_add_dashboard_widget(
            'wpwps_sync_status_widget',
            __('Printify Sync - Status', 'wp-woocommerce-printify-sync'),
            [$this, 'renderSyncStatusWidget']
        );

        wp_add_dashboard_widget(
            'wpwps_sales_chart_widget',
            __('Printify Sync - Sales', 'wp-woocommerce-printify-sync'),
            [$this, 'renderSalesChartWidget']
        );
    }

    public function renderEmailQueueWidget(): void
    {
        $data = [
            'email_queue' => [
                'pending' => $this->emailService->getPendingEmailCount(),
                'failed' => $this->emailService->getFailedEmailCount(),
                'processed' => $this->emailService->getProcessedCount24Hours(),
            ]
        ];
        
        echo $this->templateEngine->render('admin.widgets.email-queue', $data);
    }

    public function renderSyncStatusWidget(): void
    {
        $data = [
            'sync_status' => [
                'products' => $this->syncService->getProductSyncStatus(),
                'orders' => $this->syncService->getOrderSyncStatus(),
                'last_sync' => $this->syncService->getLastSyncTime(),
                'next_sync' => $this->syncService->getNextScheduledSync(),
            ],
            'api_health' => [
                'printify' => $this->printifyApi->checkApiHealth(),
                'rate_limits' => $this->printifyApi->getRateLimits(),
                'webhook_status' => $this->syncService->getWebhookStatus(),
            ]
        ];
        
        echo $this->templateEngine->render('admin.widgets.sync-status', $data);
    }

    public function renderSalesChartWidget(): void
    {
        $data = [
            'sales_data' => $this->getSalesData()
        ];
        
        echo $this->templateEngine->render('admin.widgets.sales-chart', $data);
    }

    private function getSalesData(): array
    {
        // Get sales data for last 30 days
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $end_date = date('Y-m-d');
        
        $orders = wc_get_orders([
            'date_created' => $start_date . '...' . $end_date,
            'status' => ['completed', 'processing'],
            'type' => 'shop_order',
            'limit' => -1,
        ]);

        $sales_data = [];
        $dates = [];
        $current = strtotime($start_date);
        $end = strtotime($end_date);

        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $sales_data[$date] = [
                'revenue' => 0,
                'orders' => 0,
                'items' => 0
            ];
            $dates[] = $date;
            $current = strtotime('+1 day', $current);
        }

        foreach ($orders as $order) {
            $date = $order->get_date_created()->format('Y-m-d');
            if (isset($sales_data[$date])) {
                $sales_data[$date]['revenue'] += $order->get_total();
                $sales_data[$date]['orders']++;
                $sales_data[$date]['items'] += count($order->get_items());
            }
        }

        return [
            'dates' => $dates,
            'daily' => $sales_data
        ];
    }

    private function getRecentActivity(): array
    {
        return [
            'sync_logs' => $this->syncService->getRecentLogs(5),
            'failed_emails' => $this->emailService->getRecentFailures(5),
            'api_errors' => $this->printifyApi->getRecentErrors(5)
        ];
    }
}
