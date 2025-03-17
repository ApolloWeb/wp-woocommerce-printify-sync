<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class AssetManager
{
    private const VERSION = '2025-03-15-21-38-29';
    private const ASSETS_URL = WPWPS_PLUGIN_URL . 'assets/';
    private const VENDOR_URL = WPWPS_PLUGIN_URL . 'assets/vendor/';

    public function registerAssets(): void
    {
        // Vendor CSS
        wp_register_style('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', [], '5.3.0');
        wp_register_style('wpwps-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0');
        wp_register_style('wpwps-animate', 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css', [], '4.1.1');
        wp_register_style('wpwps-sweetalert', 'https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css', [], '5.0.15');

        // Vendor JS
        wp_register_script('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', [], '5.3.0', true);
        wp_register_script('wpwps-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.0', true);
        wp_register_script('wpwps-sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], '11.7.12', true);
        wp_register_script('wpwps-gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', [], '3.12.2', true);

        // Custom assets
        $this->registerCustomAssets();
    }

    private function registerCustomAssets(): void
    {
        // Base styles
        wp_register_style(
            'wpwps-base',
            self::ASSETS_URL . 'css/base.css',
            ['wpwps-bootstrap', 'wpwps-fontawesome', 'wpwps-animate'],
            self::VERSION
        );

        // Page-specific styles
        foreach (['dashboard', 'settings', 'logs'] as $page) {
            wp_register_style(
                "wpwps-{$page}",
                self::ASSETS_URL . "css/pages/{$page}.css",
                ['wpwps-base'],
                self::VERSION
            );
        }

        // Base scripts
        wp_register_script(
            'wpwps-base',
            self::ASSETS_URL . 'js/base.js',
            ['jquery', 'wpwps-bootstrap', 'wpwps-sweetalert', 'wpwps-gsap'],
            self::VERSION,
            true
        );

        // Page-specific scripts
        $this->registerPageScripts();
    }
}