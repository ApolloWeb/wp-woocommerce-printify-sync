<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;

class ProductSyncSummaryWidget extends AbstractWidget
{
    public static function render()
    {
        $data = [
            'sync_summary' => [
                'synced' => 120,
                'failed' => 5,
                'pending' => 10
            ]
        ];

        self::getTemplate('product-sync-summary-widget', $data);
    }
}