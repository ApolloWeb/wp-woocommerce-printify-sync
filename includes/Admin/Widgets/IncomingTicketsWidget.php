<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;

class IncomingTicketsWidget extends AbstractWidget
{
    public static function render()
    {
        $data = [
            'tickets' => [
                ['id' => 1, 'type' => 'Refund Request', 'subject' => 'Ticket 1', 'status' => 'Open'],
                ['id' => 2, 'type' => 'Product Inquiry', 'subject' => 'Ticket 2', 'status' => 'Closed']
            ]
        ];

        self::getTemplate('incoming-tickets-widget', $data);
    }
}