<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Services\BladeTemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyAPI;

class Dashboard {
    private $template;
    private $printifyAPI;

    public function __construct(BladeTemplateEngine $template, PrintifyAPI $printifyAPI) {
        $this->template = $template;
        $this->printifyAPI = $printifyAPI;

        // Register AJAX handlers
        add_action('wp_ajax_wpwps_get_dashboard_data', [$this, 'getDashboardData']);
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void {
        // Get initial dashboard data
        $data = [
            'email_queue' => $this->getEmailQueueStats(),
            'import_queue' => $this->getImportQueueStats(),
            'sync_status' => $this->getSyncStatus(),
            'api_health' => $this->getAPIHealth(),
            'sales_data' => $this->getSalesData(),
        ];

        echo $this->template->render('wpwps-dashboard', $data);
    }

    public function enqueueAssets(string $hook): void {
        if ($hook !== 'toplevel_page_wpwps-dashboard') {
            return;
        }

        // Enqueue Google Fonts - Inter
        wp_enqueue_style(
            'google-fonts-inter',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
        );

        // Enqueue Bootstrap with utilities
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css'
        );
        wp_enqueue_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
            ['jquery'],
            null,
            true
        );

        // Enqueue Font Awesome 6
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
        );

        // Enqueue Chart.js with animations plugin
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            null,
            true
        );

        // Our custom shared assets
        wp_enqueue_script(
            'wpwps-toast',
            WPWPS_URL . 'assets/js/shared/toast.js',
            ['jquery', 'bootstrap'],
            WPWPS_VERSION,
            true
        );

        // Our custom assets
        wp_enqueue_style(
            'wpwps-dashboard',
            WPWPS_URL . 'assets/css/wpwps-dashboard.css',
            [],
            WPWPS_VERSION
        );
        wp_enqueue_script(
            'wpwps-dashboard',
            WPWPS_URL . 'assets/js/wpwps-dashboard.js',
            ['jquery', 'bootstrap', 'chartjs', 'wpwps-toast'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-dashboard', 'wpwps_dashboard', [
            'nonce' => wp_create_nonce('wpwps_dashboard_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'is_rtl' => is_rtl(),
            'user' => [
                'display_name' => wp_get_current_user()->display_name,
                'avatar' => get_avatar_url(get_current_user_id()),
                'role' => array_values(wp_get_current_user()->roles)[0]
            ]
        ]);
    }

    public function getDashboardData(): void {
        check_ajax_referer('wpwps_dashboard_nonce', 'nonce');

        wp_send_json_success([
            'email_queue' => $this->getEmailQueueStats(),
            'import_queue' => $this->getImportQueueStats(),
            'sync_status' => $this->getSyncStatus(),
            'api_health' => $this->getAPIHealth(),
            'sales_data' => $this->getSalesData(),
        ]);
    }

    private function getEmailQueueStats(): array {
        global $wpdb;
        
        $queued = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue WHERE status = 'queued'");
        $sent = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue WHERE status = 'sent' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $failed = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue WHERE status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");

        return [
            'queued' => (int) $queued,
            'sent_24h' => (int) $sent,
            'failed_24h' => (int) $failed,
        ];
    }

    private function getImportQueueStats(): array {
        $scheduler = \ActionScheduler::store();
        
        $pending = $scheduler->query_actions([
            'hook' => 'wpwps_process_product_import',
            'status' => \ActionScheduler_Store::STATUS_PENDING,
        ]);

        $running = $scheduler->query_actions([
            'hook' => 'wpwps_process_product_import',
            'status' => \ActionScheduler_Store::STATUS_RUNNING,
        ]);

        $completed = $scheduler->query_actions([
            'hook' => 'wpwps_process_product_import',
            'status' => \ActionScheduler_Store::STATUS_COMPLETE,
            'date' => gmdate('Y-m-d H:i:s', strtotime('-24 hours')),
        ]);

        return [
            'pending' => count($pending),
            'running' => count($running),
            'completed_24h' => count($completed),
        ];
    }

    private function getSyncStatus(): array {
        $last_sync = get_option('wpwps_last_sync_time');
        $sync_status = get_option('wpwps_sync_status', 'idle');
        $sync_progress = get_option('wpwps_sync_progress', 0);
        
        return [
            'last_sync' => $last_sync ? human_time_diff(strtotime($last_sync)) : __('Never', 'wp-woocommerce-printify-sync'),
            'status' => $sync_status,
            'progress' => (int) $sync_progress,
        ];
    }

    private function getAPIHealth(): array {
        $printify_health = true;
        $error_message = '';

        try {
            // Quick API health check
            $this->printifyAPI->getShops();
        } catch (\Exception $e) {
            $printify_health = false;
            $error_message = $e->getMessage();
        }

        return [
            'printify' => [
                'healthy' => $printify_health,
                'error' => $error_message,
                'rate_limit' => get_transient('wpwps_printify_rate_limit'),
            ],
            'webhook' => [
                'healthy' => get_option('wpwps_webhook_healthy', true),
                'last_received' => get_option('wpwps_last_webhook_time'),
            ],
        ];
    }

    private function getSalesData(): array {
        global $wpdb;

        // Get sales data for the last 30 days
        $results = $wpdb->get_results("
            SELECT 
                DATE(post_date) as date,
                COUNT(*) as orders,
                SUM(meta_value) as revenue
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE 
                p.post_type = 'shop_order'
                AND p.post_status IN ('wc-completed', 'wc-processing')
                AND pm.meta_key = '_order_total'
                AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(post_date)
            ORDER BY date ASC
        ");

        $dates = [];
        $orders = [];
        $revenue = [];

        foreach ($results as $row) {
            $dates[] = $row->date;
            $orders[] = (int) $row->orders;
            $revenue[] = (float) $row->revenue;
        }

        return [
            'dates' => $dates,
            'orders' => $orders,
            'revenue' => $revenue,
        ];
    }
}