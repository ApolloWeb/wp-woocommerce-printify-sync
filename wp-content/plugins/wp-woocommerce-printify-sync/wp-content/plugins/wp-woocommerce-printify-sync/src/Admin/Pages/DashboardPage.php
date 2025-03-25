<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

/**
 * Class DashboardPage
 *
 * Renders the dashboard page.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Pages
 */
class DashboardPage
{
    /**
     * Render the dashboard page.
     */
    public static function render(): void
    {
        include plugin_dir_path(__FILE__) . '../../templates/wpwps-dashboard.blade.php';
    }
}
