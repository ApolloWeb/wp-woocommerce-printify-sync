<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ProductImport
{
    public static function register()
    {
        // Register actions and hooks for Product Import page
    }

    public static function renderPage()
    {
        include plugin_dir_path(__FILE__) . '../../templates/admin/products-import.php';
    }
}