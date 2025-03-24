<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Services\Container;

class ProductsPage {
    private Container $container;
    
    public function __construct(Container $container) {
        $this->container = $container;
    }
    
    public function render(): void {
        $template = $this->container->get('template');
        $template->render('admin/products');
    }
}
