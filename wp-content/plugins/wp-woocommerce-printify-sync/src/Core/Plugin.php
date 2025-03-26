<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;
use BladeOne;

class Plugin {
    private array $providers = [];
    private View $view;

    public function boot(): void {
        $this->setupView();
        $this->registerAssets();
        $this->loadProviders();
        $this->bootProviders();
    }

    private function setupView(): void {
        $viewPath = plugin_dir_path(dirname(__DIR__)) . 'templates';
        $cachePath = plugin_dir_path(dirname(__DIR__)) . 'templates/cache';
        
        if (!is_dir($cachePath)) {
            wp_mkdir_p($cachePath);
        }
        
        $this->view = new View($viewPath, $cachePath);
    }

    private function registerAssets(): void {
        add_action('admin_enqueue_scripts', function() {
            // Core assets
            wp_enqueue_style('wpwps-bootstrap', plugins_url('/assets/core/css/bootstrap.min.css', dirname(__DIR__)));
            wp_enqueue_style('wpwps-fontawesome', plugins_url('/assets/core/css/fontawesome.min.css', dirname(__DIR__)));
            wp_enqueue_script('wpwps-bootstrap', plugins_url('/assets/core/js/bootstrap.bundle.min.js', dirname(__DIR__)), ['jquery'], null, true);
            wp_enqueue_script('wpwps-chartjs', plugins_url('/assets/core/js/chart.min.js', dirname(__DIR__)), [], null, true);

            // Custom assets
            wp_enqueue_style('wpwps-dashboard', plugins_url('/assets/css/wpwps-dashboard.css', dirname(__DIR__)));
            wp_localize_script('wpwps-dashboard', 'wpwps', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpwps_ajax_nonce')
            ]);
        });
    }

    private function loadProviders(): void {
        $this->providers = [
            \ApolloWeb\WPWooCommercePrintifySync\Providers\DashboardProvider::class,
            \ApolloWeb\WPWooCommercePrintifySync\Providers\SettingsProvider::class,
            \ApolloWeb\WPWooCommercePrintifySync\Providers\OrderProvider::class,
            \ApolloWeb\WPWooCommercePrintifySync\Providers\ProductProvider::class,
            \ApolloWeb\WPWooCommercePrintifySync\Providers\ShippingProvider::class,
        ];
    }

    private function bootProviders(): void {
        foreach ($this->providers as $provider) {
            (new $provider($this->view))->boot();
        }
    }
}