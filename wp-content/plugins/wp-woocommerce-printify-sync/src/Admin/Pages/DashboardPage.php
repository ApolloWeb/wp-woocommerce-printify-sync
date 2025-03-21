<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AbstractAdminPage;

class DashboardPage extends AbstractAdminPage
{
    public function __construct()
    {
        $this->template = 'wpwps-dashboard';
        $this->pageSlug = 'dashboard';
    }
}
