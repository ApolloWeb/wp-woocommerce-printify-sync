<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Admin\PageController;

class ProductsController extends PageController {
    public function __construct($template) {
        parent::__construct($template);
        $this->title = 'Products';
        $this->addAction('Import Products', 'import-products', [], 'btn-primary', 'fas fa-download');
        $this->addAction('Sync Selected', 'sync-selected', ['disabled' => 'true'], 'btn-outline-primary', 'fas fa-sync');
    }

    protected function getTemplate(): string {
        return 'admin/products';
    }
}
