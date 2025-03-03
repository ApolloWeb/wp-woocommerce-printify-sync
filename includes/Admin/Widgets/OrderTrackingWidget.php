<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;

class OrderTrackingWidget extends AbstractWidget
{
    public static function render()
    {
        $data = [
            'orders' => [
                ['id' => 1, 'tracking_number' => '123456'],
                ['id' => 2, 'tracking_number' => '789012']
            ]
        ];

        self::getTemplate('order-tracking-widget', $data);
    }
}