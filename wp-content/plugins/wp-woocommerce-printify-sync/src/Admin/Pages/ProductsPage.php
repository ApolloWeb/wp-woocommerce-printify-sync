<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

class ProductsPage extends AbstractPage
{
    public function __construct()
    {
        $this->template = 'wpwps-products';
        $this->pageSlug = 'products';
    }
}
