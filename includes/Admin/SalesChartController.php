<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class SalesChartController {

    public static function init() {
        // Register AJAX action for sales data
        add_action('wp_ajax_get_sales_data', [self::class, 'get_sales_data']);
    }

    public static function get_sales_data() {
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'day';

        // Dummy data for demonstration
        $data = [
            'labels' => [],
            'sales' => []
        ];

        $current_date = new \DateTime();
        switch ($filter) {
            case 'day':
                for ($i = 0; $i < 24; $i++) {
                    $data['labels'][] = $i . ':00';
                    $data['sales'][] = rand(0, 100); // Replace with actual sales data
                }
                break;
            case 'week':
                for ($i = 0; $i < 7; $i++) {
                    $data['labels'][] = $current_date->modify('-1 day')->format('Y-m-d');
                    $data['sales'][] = rand(0, 100); // Replace with actual sales data
                }
                break;
            case 'month':
                for ($i = 0; $i < 30; $i++) {
                    $data['labels'][] = $current_date->modify('-1 day')->format('Y-m-d');
                    $data['sales'][] = rand(0, 100); // Replace with actual sales data
                }
                break;
            case 'year':
                for ($i = 0; $i < 12; $i++) {
                    $data['labels'][] = $current_date->modify('-1 month')->format('Y-m');
                    $data['sales'][] = rand(0, 100); // Replace with actual sales data
                }
                break;
        }

        echo json_encode($data);
        wp_die();
    }
}