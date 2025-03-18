<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminDashboard {
    public function register(): void {
        add_action('admin_menu', [$this, 'addAdminMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }
    public function addAdminMenus(): void {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-settings',
            [$this, 'renderSettingsPage'],
            'dashicons-admin-generic',
            25
        );
        add_submenu_page(
            'wpwps-settings',
            __('Product Import', 'wp-woocommerce-printify-sync'),
            __('Product Import', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-product-import',
            [$this, 'renderProductImportPage']
        );
    }
    public function enqueueAdminAssets($hook): void {
        if (false === strpos($hook, 'wpwps')) {
            return;
        }

        // Use a fallback version if WPWPS_VERSION is not defined.
        $version = defined('WPWPS_VERSION') ? WPWPS_VERSION : '2.0.0';

        wp_enqueue_style(
            'wpwps-settings',
            plugins_url('assets/css/wpwps-settings.css', dirname(__DIR__, 2)),
            [],
            $version
        );
        wp_enqueue_script(
            'wpwps-settings',
            plugins_url('assets/js/wpwps-settings.js', dirname(__DIR__, 2)),
            ['jquery'],
            $version,
            true
        );
        wp_enqueue_script(
            'wpwps-admin',
            plugins_url('assets/js/wpwps-admin.js', dirname(__DIR__, 2)),
            ['jquery'],
            $version,
            true
        );
        
        // Enqueue external libraries.
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );
        wp_enqueue_script(
            'chart-js',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.2.1/chart.min.js',
            [],
            '4.2.1',
            true
        );
        wp_enqueue_style(
            'adminlte',
            'https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css',
            [],
            '3.2.0'
        );
        wp_enqueue_script(
            'adminlte',
            'https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js',
            ['jquery'],
            '3.2.0',
            true
        );

        wp_localize_script(
            'wpwps-settings',
            'wpwpsAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('wpwps_nonce'),
            ]
        );
    }
    public function renderSettingsPage(): void {
        include plugin_dir_path(dirname(__FILE__, 3)) . 'templates/settings-page.blade.php';
    }
    public function renderProductImportPage(): void {
        include plugin_dir_path(dirname(__FILE__, 3)) . 'templates/product-import-page.blade.php';
    }
}
