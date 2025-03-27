<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;
use BladeOne;

class Plugin {
    private array $providers = [];
    private View $view;

    public function boot(): void {
        $this->setupView();
        $this->registerProviders();
        $this->bootProviders();
        $this->initHooks();
    }

    private function setupView(): void {
        $viewPath = plugin_dir_path(dirname(__DIR__)) . 'templates';
        $cachePath = plugin_dir_path(dirname(__DIR__)) . 'templates/cache';
        
        if (!is_dir($cachePath)) {
            wp_mkdir_p($cachePath);
        }
        
        $this->view = new View($viewPath, $cachePath);
    }

    private function registerProviders(): void {
        $this->providers = [
            \ApolloWeb\WPWooCommercePrintifySync\Providers\DashboardProvider::class,
            \ApolloWeb\WPWooCommercePrintifySync\Providers\SettingsProvider::class,
            \ApolloWeb\WPWooCommercePrintifySync\Providers\OrderProvider::class,
            \ApolloWeb\WPWooCommercePrintifySync\Providers\ProductProvider::class,
            \ApolloWeb\WPWooCommercePrintifySync\Providers\ShippingProvider::class,
        ];

        foreach ($this->providers as $provider) {
            (new $provider($this->view))->register();
        }
    }

    private function bootProviders(): void {
        foreach ($this->providers as $provider) {
            (new $provider($this->view))->boot();
        }
    }

    private function initHooks(): void {
        add_action('init', [$this, 'loadTextDomain']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function loadTextDomain(): void {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(WPWPS_BASENAME) . '/languages'
        );
    }

    public function enqueueAssets(): void {
        wp_enqueue_style(
            'wpwps-admin',
            WPWPS_URL . 'assets/css/wpwps-admin.css',
            [],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-admin',
            WPWPS_URL . 'assets/js/wpwps-admin.js',
            ['jquery'],
            WPWPS_VERSION,
            true
        );
    }
}