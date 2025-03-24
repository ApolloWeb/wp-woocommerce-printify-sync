<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard;

use ApolloWeb\WPWooCommercePrintifySync\Core\View;

class DashboardManager
{
    private $widgets = [];

    public function __construct()
    {
        add_action('wp_dashboard_setup', [$this, 'registerDashboardWidgets']);
        $this->initializeWidgets();
    }

    private function initializeWidgets(): void
    {
        $this->widgets = [
            new Widgets\EmailQueueWidget(),
            new Widgets\ImportProgressWidget(),
            new Widgets\SyncStatusWidget(),
            new Widgets\SalesChartWidget(),
            new Widgets\APIHealthWidget()
        ];
    }

    public function registerDashboardWidgets(): void
    {
        foreach ($this->widgets as $widget) {
            wp_add_dashboard_widget(
                $widget->getId(),
                $widget->getTitle(),
                [$widget, 'render']
            );
        }
    }
}
