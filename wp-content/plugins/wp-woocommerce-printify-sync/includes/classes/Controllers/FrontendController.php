<?php

namespace ApolloWeb\WpWooCommercePrintifySync\Controllers;

use ApolloWeb\WpWooCommercePrintifySync\Abstracts\BaseController;

class FrontendController extends BaseController
{
    public function __construct()
    {
        // Initialize frontend-specific functionality here
    }

    public function renderFrontendPage()
    {
        echo $this->renderView('frontend.example', ['name' => 'ApolloWeb']);
    }
}