<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class Enqueue {
    public static function loadAssets(): void {
        $base = plugins_url('assets', dirname(__FILE__, 3));

        wp_enqueue_style('wpwps-bootstrap', $base . '/core/css/bootstrap.min.css', [], '5.3.3');
        wp_enqueue_style('wpwps-fontawesome', $base . '/core/css/fontawesome.min.css', [], '6.5.1');
        wp_enqueue_script('wpwps-fontawesome', $base . '/core/js/fontawesome.min.js', [], '6.5.1', true);
        wp_enqueue_script('wpwps-bootstrap', $base . '/core/js/bootstrap.bundle.min.js', [], '5.3.3', true);
        wp_enqueue_script('wpwps-chartjs', $base . '/core/js/chart.min.js', [], '4.4.1', true);

        wp_enqueue_style('wpwps-admin', $base . '/css/wpwps-dashboard.css', [], null);
        wp_enqueue_script('wpwps-admin', $base . '/js/wpwps-dashboard.js', [], null, true);
    }
}
