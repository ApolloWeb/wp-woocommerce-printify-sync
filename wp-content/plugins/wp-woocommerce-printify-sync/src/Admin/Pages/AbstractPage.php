<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AbstractAdminPage;

abstract class AbstractPage extends AbstractAdminPage
{
    public function render(): void
    {
        $this->renderTemplate();
    }

    protected function getPageTitle(): string
    {
        return '';
    }
}
