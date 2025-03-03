<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class PostmanPage
{
    public static function register()
    {
        // Register actions and hooks for Postman page
    }

    public static function renderPage()
    {
        include plugin_dir_path(__FILE__) . '../../templates/admin/postman-page.php';
    }
}