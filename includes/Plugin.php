<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

defined('ABSPATH') || exit;

class Plugin
{
    public function init(): void
    {
        add_action('init', [$this, 'loadTextDomain']);
        add_action('admin_menu', [$this, 'registerAdminPages']);
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain('wp-woocommerce-printify-sync', false, dirname(plugin_basename(__FILE__), 2) . '/languages');
    }

    public function registerAdminPages(): void
    {
        // Add submenus here
    }
}
