<?php

namespace ApolloWeb\WpWooCommercePrintifySync\Controllers;

use ApolloWeb\WpWooCommercePrintifySync\Abstracts\BaseController;

class AdminController extends BaseController
{
    public function __construct()
    {
        // Initialize admin-specific functionality here
    }

    public function renderAdminPage()
    {
        echo $this->renderView('admin.dashboard', ['title' => 'Admin Dashboard']);
    }
}