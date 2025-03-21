<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Assets;

class Plugin
{
    public function __construct()
    {
        if (!$this->checkDependencies()) {
            return;
        }
        $this->initHooks();
    }

    private function checkDependencies(): bool
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . 
                     esc_html__('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated.', 'wp-woocommerce-printify-sync') . 
                     '</p></div>';
            });
            return false;
        }
        return true;
    }

    private function initHooks(): void
    {
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
        add_action('admin_menu', [new AdminMenu(), 'register']);
        add_action('admin_enqueue_scripts', [new Assets(), 'register']);
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
}
