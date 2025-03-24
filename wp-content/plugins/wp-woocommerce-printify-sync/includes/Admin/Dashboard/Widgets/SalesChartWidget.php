<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard\AbstractWidget;

class SalesChartWidget extends AbstractWidget
{
    protected $id = 'sales_chart';
    protected $title = 'Sales Overview';

    protected function getData(): array
    {
        return [
            'labels' => $this->getLastNDays(30),
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => $this->getSalesData(),
                    'borderColor' => '#96588a',
                    'fill' => false
                ]
            ]
        ];
    }

    private function getLastNDays(int $days): array
    {
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates[] = date('M j', strtotime("-$i days"));
        }
        return $dates;
    }

    private function getSalesData(): array
    {
        global $wpdb;
        $data = [];
        
        // Get last 30 days of sales
        $results = $wpdb->get_results("
            SELECT DATE(post_date) as date, SUM(meta_value) as total
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE post_type = 'shop_order'
            AND meta_key = '_order_total'
            AND post_status IN ('wc-completed', 'wc-processing')
            AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(post_date)
            ORDER BY date ASC
        ");

        // Fill in zero values for days without sales
        $sales_by_date = array_column($results, 'total', 'date');
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data[] = $sales_by_date[$date] ?? 0;
        }

        return $data;
    }
}
