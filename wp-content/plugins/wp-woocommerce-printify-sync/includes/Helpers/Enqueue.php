<?php

namespace ApolloWeb\WpWooCommercePrintifySync\Helpers;

class Enqueue
{
    public static function adminAssets()
    {
        wp_enqueue_style('adminlte', WPWPSP_PLUGIN_URL . 'assets/adminlte/css/adminlte.min.css', [], '3.1.0');
        wp_enqueue_style('adminlte-all', WPWPSP_PLUGIN_URL . 'assets/adminlte/css/all.min.css', [], '3.1.0');
        wp_enqueue_style('font-awesome', WPWPSP_PLUGIN_URL . 'assets/adminlte/css/all.min.css', [], '5.15.4');
        wp_enqueue_style('admin-dashboard', WPWPSP_PLUGIN_URL . 'assets/admin/css/admin-dashboard.css', [], '1.0.0');

        wp_enqueue_script('adminlte', WPWPSP_PLUGIN_URL . 'assets/adminlte/js/adminlte.min.js', ['jquery'], '3.1.0', true);
        wp_enqueue_script('adminlte-bootstrap-bundle', WPWPSP_PLUGIN_URL . 'assets/adminlte/js/bootstrap.bundle.min.js', ['jquery'], '3.1.0', true);
        wp_enqueue_script('chartjs', WPWPSP_PLUGIN_URL . 'assets/adminlte/plugins/chart.js/Chart.min.js', ['jquery'], '3.5.1', true);
        wp_enqueue_script('admin-dashboard', WPWPSP_PLUGIN_URL . 'assets/admin/js/admin-dashboard.js', ['jquery', 'chartjs'], '1.0.0', true);
    }
}