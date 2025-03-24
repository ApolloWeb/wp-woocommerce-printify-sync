<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu;
use ApolloWeb\WPWooCommercePrintifySync\Core\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Core\AssetsManager;
use ApolloWeb\WPWooCommercePrintifySync\Core\View;

class Plugin
{
    private static $instance = null;
    private $settings;
    private $assets;
    
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void
    {
        View::init();
        $this->settings = new Settings();
        $this->assets = new AssetsManager();
        
        if (is_admin()) {
            new AdminMenu();
            new Admin\Dashboard\DashboardManager();
        }
        
        $this->initHooks();
    }

    private function initHooks(): void
    {
        add_action('init', [$this, 'loadTextDomain']);
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(WPWPS_PLUGIN_BASENAME) . '/languages'
        );
    }
}
