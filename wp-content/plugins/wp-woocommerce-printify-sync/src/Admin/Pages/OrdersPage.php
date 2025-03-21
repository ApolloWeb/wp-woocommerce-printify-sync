<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

class OrdersPage extends AbstractPage
{
    public function __construct()
    {
        $this->template = 'wpwps-orders';
        $this->pageSlug = 'orders';
    }
}
