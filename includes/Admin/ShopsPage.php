<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ShopsPage
{
    public static function register()
    {
        // Register actions and hooks for Shops page
    }

    public static function renderPage()
    {
        include plugin_dir_path(__FILE__) . '../../templates/admin/shops-page.php';
    }
}