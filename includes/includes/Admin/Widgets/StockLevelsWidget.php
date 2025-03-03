<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;

class StockLevelsWidget extends AbstractWidget
{
    public static function render()
    {
        $data = [
            'stock_levels' => [
                'in_stock' => 300,
                'low_stock' => 50,
                'out_of_stock' => 20
            ]
        ];

        self::getTemplate('stock-levels-widget', $data);
    }
}