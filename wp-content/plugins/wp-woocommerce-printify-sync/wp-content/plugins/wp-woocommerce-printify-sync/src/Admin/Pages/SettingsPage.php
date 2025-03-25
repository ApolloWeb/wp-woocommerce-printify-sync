<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

class SettingsPage
{
    public static function render()
    {
        // Render the settings page
        include plugin_dir_path(__FILE__) . '../../templates/wpwps-settings.blade.php';
    }
}
