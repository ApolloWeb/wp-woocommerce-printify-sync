<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Admin\PageController;

class OrdersController extends PageController {
    public function __construct($template) {
        parent::__construct($template);
        $this->title = 'Orders';
        $this->addAction('Sync Orders', 'sync-orders', [], 'btn-primary', 'fas fa-sync');
    }

    protected function getTemplate(): string {
        return 'admin/orders';
    }
}
