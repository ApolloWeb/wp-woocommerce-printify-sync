<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard;

class DashboardManager
{
    private const LAYOUT_OPTION = 'wpwps_dashboard_layout';
    private const WIDGET_OPTION = 'wpwps_dashboard_widgets';

    public function initialize(): void
    {
        add_action('admin_menu', [$this, 'addDashboardPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_save_dashboard_layout', [$this, 'saveDashboardLayout']);
        add_action('wp_ajax_wpwps_save_widget_settings', [$this, 'saveWidgetSettings']);
    }

    public function addDashboardPage(): void
    {
        add_menu_page(
            'Printify Sync Dashboard',
            'Printify Sync',
            'manage_options',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-chart-area',
            30
        );
    }

    public function renderDashboard(): void
    {
        $layout = $this->getDashboardLayout();
        $widgets = $this->getAvailableWidgets();
        $settings = $this->getWidgetSettings();

        include WPWPS_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    public function enqueueAssets(string $hook): void
    {
        if ('toplevel_page_wpwps-dashboard' !== $hook) {
            return;
        }

        wp_enqueue_script('wpwps-gridstack', plugins_url('assets/js/lib/gridstack.min.js', WPWPS_PLUGIN_FILE), [], WPWPS_VERSION, true);
        wp_enqueue_script('wpwps-dashboard', plugins_url('assets/js/admin/dashboard.js', WPWPS_PLUGIN_FILE), ['jquery', 'wpwps-gridstack'], WPWPS_VERSION, true);
        wp_enqueue_style('wpwps-gridstack', plugins_url('assets/css/lib/gridstack.min.css', WPWPS_PLUGIN_FILE), [], WPWPS_VERSION);
        wp_enqueue_style('wpwps-dashboard', plugins_url('assets/css/admin/dashboard.css', WPWPS_PLUGIN_FILE), [], WPWPS_VERSION);

        wp_localize_script('wpwps-dashboard', 'wpwpsDashboard', [
            'nonce' => wp_create_nonce('wpwps_dashboard'),
            'layout' => $this->getDashboardLayout(),
            'widgets' => $this->getAvailableWidgets(),
            'settings' => $this->getWidgetSettings(),
        ]);
    }

    private function getDashboardLayout(): array
    {
        $default_layout = [
            'sync_status' => ['x' => 0, 'y' => 0, 'w' => 6, 'h' => 4],
            'api_metrics' => ['x' => 6, 'y' => 0, 'w' => 6, 'h' => 4],
            'recent_orders' => ['x' => 0, 'y' => 4, 'w' => 12, 'h' => 4],
            'error_log' => ['x' => 0, 'y' => 8, 'w' => 6, 'h' => 4],
            'performance' => ['x' => 6, 'y' => 8, 'w' => 6, 'h' => 4],
        ];

        return get_option(self::LAYOUT_OPTION, $default_layout);
    }

    private function getAvailableWidgets(): array
    {
        return [
            'sync_status' => [
                'title' => 'Sync Status',
                'description' => 'Display current sync status and progress',
                'refresh_interval' => 30,
                'type' => 'chart',
                'settings' => [
                    'chart_type' => ['pie', 'donut', 'bar'],
                    'show_legend' => true,
                    'animate' => true,
                ],
            ],
            'api_metrics' => [
                'title' => 'API Metrics',
                'description' => 'Monitor API performance and usage',
                'refresh_interval' => 60,
                'type' => 'chart',
                'settings' => [
                    'chart_type' => ['line', 'area', 'bar'],
                    'time_range' => ['1h', '24h', '7d', '30d'],
                    'metrics' => ['response_time', 'requests', 'errors'],
                ],
            ],
            'recent_orders' => [
                'title' => 'Recent Orders',
                'description' => 'View and manage recent orders',
                'refresh_interval' => 300,
                'type' => 'grid',
                'settings' => [
                    'page_size' => [10, 25, 50, 100],
                    'columns' => [
                        'order_id' => 'Order ID',
                        'status' => 'Status',
                        'created_at' => 'Created',
                        'total' => 'Total',
                    ],
                ],
            ],
            'error_log' => [
                'title' => 'Error Log',
                'description' => 'Monitor and analyze errors',
                'refresh_interval' => 60,
                'type' => 'list',
                'settings' => [
                    'max_items' => [10, 25, 50, 100],
                    'severity_filter' => ['error', 'warning', 'info'],
                    'auto_refresh' => true,
                ],
            ],
            'performance' => [
                'title' => 'Performance Metrics',
                'description' => 'Track system performance',
                'refresh_interval' => 60,
                'type' => 'chart',
                'settings' => [
                    'chart_type' => ['gauge', 'line', 'bar'],
                    'metrics' => ['memory', 'cpu', 'requests'],
                    'threshold_warning' => 70,
                    'threshold_critical' => 90,
                ],
            ],
        ];
    }

    private function getWidgetSettings(): array
    {
        return get_option(self::WIDGET_OPTION, []);
    }

    public function saveDashboardLayout(): void
    {
        check_ajax_referer('wpwps_dashboard');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $layout = json_decode(stripslashes($_POST['layout']), true);
        if (!is_array($layout)) {
            wp_send_json_error('Invalid layout data');
        }

        update_option(self::LAYOUT_OPTION, $layout);
        wp_send_json_success();
    }

    public function saveWidgetSettings(): void
    {
        check_ajax_referer('wpwps_dashboard');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $widget_id = sanitize_text_field($_POST['widget_id']);
        $settings = json_decode(stripslashes($_POST['settings']), true);

        if (!$widget_id || !is_array($settings)) {
            wp_send_json_error('Invalid widget settings');
        }

        $current_settings = $this->getWidgetSettings();
        $current_settings[$widget_id] = $settings;
        update_option(self::WIDGET_OPTION, $current_settings);

        wp_send_json_success();
    }
}