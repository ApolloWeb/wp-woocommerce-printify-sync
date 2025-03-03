<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ExchangeRatesPage
{
    public static function register()
    {
        // Register actions and hooks for Exchange Rates page
    }

    public static function renderPage()
    {
        include plugin_dir_path(__FILE__) . '../../templates/admin/exchange-rates-page.php';
    }
}