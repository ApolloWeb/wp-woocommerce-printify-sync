<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminMenu
{
    public function register(): void
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-admin-generic'
        );

        add_submenu_page(
            'wpwps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-settings',
            [$this, 'renderSettings']
        );
    }

    public function renderDashboard(): void
    {
        echo '<div class="wrap"><h1>' . __('Printify Sync Dashboard', 'wp-woocommerce-printify-sync') . '</h1></div>';
    }

    public function renderSettings(): void
    {
        echo '<div class="wrap"><h1>' . __('Printify Sync Settings', 'wp-woocommerce-printify-sync') . '</h1></div>';
    }
}