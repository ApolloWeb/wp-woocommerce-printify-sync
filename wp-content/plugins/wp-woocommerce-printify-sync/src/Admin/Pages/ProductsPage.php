<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

class ProductsPage extends AbstractAdminPage
{
    public function __construct($templateEngine)
    {
        parent::__construct($templateEngine);
        $this->slug = 'wpwps-products';
        $this->pageTitle = 'Printify Products';
        $this->menuTitle = 'Products';
    }

    public function render()
    {
        return $this->templateEngine->render('admin/wpwps-products.php', [
            'partials' => ['wpwps-header', 'wpwps-alerts', 'wpwps-filters']
        ]);
    }

    public function getRequiredAssets(): array
    {
        return [
            'styles' => ['wpwps-products', 'wpwps-common'],
            'scripts' => ['wpwps-products']
        ];
    }
}
