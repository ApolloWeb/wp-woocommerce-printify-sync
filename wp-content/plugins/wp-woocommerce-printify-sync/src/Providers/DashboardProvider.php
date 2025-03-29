<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

class DashboardProvider implements ServiceProvider
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-store',
            58
        );
    }

    public function renderDashboard(): void
    {
        echo View::render('wpwps-dashboard', [
            'title' => __('Printify Sync Dashboard', 'wp-woocommerce-printify-sync')
        ]);
    }

    public function enqueueAssets(): void
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpwps-dashboard') {
            return;
        }

        wp_enqueue_style('wpwps-bootstrap', WPWPS_URL . 'assets/core/css/bootstrap.min.css', [], WPWPS_VERSION);
        wp_enqueue_style('wpwps-fontawesome', WPWPS_URL . 'assets/core/css/fontawesome.min.css', [], WPWPS_VERSION);
        wp_enqueue_style('wpwps-dashboard', WPWPS_URL . 'assets/css/wpwps-dashboard.css', [], WPWPS_VERSION);
        
        wp_enqueue_script('wpwps-bootstrap', WPWPS_URL . 'assets/core/js/bootstrap.bundle.min.js', ['jquery'], WPWPS_VERSION, true);
        wp_enqueue_script('wpwps-chartjs', WPWPS_URL . 'assets/core/js/chart.min.js', [], WPWPS_VERSION, true);
        wp_enqueue_script('wpwps-dashboard', WPWPS_URL . 'assets/js/wpwps-dashboard.js', ['jquery'], WPWPS_VERSION, true);
    }
}