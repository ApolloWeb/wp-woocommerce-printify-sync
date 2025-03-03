<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;

class OrdersOverviewWidget extends AbstractWidget
{
    public static function render()
    {
        // Data for the graph can be more complex; this is just an example
        $data = [
            'orders_today' => 10,
            'orders_week' => 50,
            'orders_month' => 200
        ];

        self::getTemplate('orders-overview-widget', $data);
    }
}