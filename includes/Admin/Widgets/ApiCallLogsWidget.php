<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;

class ApiCallLogsWidget extends AbstractWidget
{
    public static function render()
    {
        $data = [
            'api_calls' => [
                ['id' => 1, 'request' => 'GET /products', 'status' => 'Success'],
                ['id' => 2, 'request' => 'POST /orders', 'status' => 'Failure']
            ]
        ];

        self::getTemplate('api-call-logs-widget', $data);
    }
}