<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

class ShippingPage extends AbstractPage
{
    public function __construct()
    {
        $this->template = 'wpwps-shipping';
        $this->pageSlug = 'shipping';
    }
}
