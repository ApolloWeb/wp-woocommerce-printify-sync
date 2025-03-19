<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

class OrdersPage extends AbstractAdminPage
{
    public function __construct($templateEngine)
    {
        parent::__construct($templateEngine);
        $this->slug = 'wpwps-orders';
        $this->pageTitle = 'Printify Orders';
        $this->menuTitle = 'Orders';
    }

    public function render()
    {
        return $this->templateEngine->render('admin/wpwps-orders.php', [
            'partials' => ['wpwps-header', 'wpwps-alerts']
        ]);
    }

    public function getRequiredAssets(): array
    {
        return [
            'styles' => ['wpwps-orders', 'wpwps-common'],
            'scripts' => ['wpwps-orders']
        ];
    }
}
