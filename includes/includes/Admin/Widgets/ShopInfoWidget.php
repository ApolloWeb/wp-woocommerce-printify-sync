<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\AbstractWidget;

class ShopInfoWidget extends AbstractWidget
{
    public static function render()
    {
        $data = [
            'shop_info' => [
                'default_shop' => 'Main Shop',
                'api_endpoint' => 'https://api.printify.com/v1/'
            ]
        ];

        self::getTemplate('shop-info-widget', $data);
    }
}