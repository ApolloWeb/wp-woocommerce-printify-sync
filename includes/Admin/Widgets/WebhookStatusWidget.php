<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;

class WebhookStatusWidget extends AbstractWidget
{
    public static function render()
    {
        $data = [
            'webhooks' => [
                ['id' => 1, 'response' => 'Success', 'status' => 'Active'],
                ['id' => 2, 'response' => 'Failure', 'status' => 'Inactive']
            ]
        ];

        self::getTemplate('webhook-status-widget', $data);
    }
}