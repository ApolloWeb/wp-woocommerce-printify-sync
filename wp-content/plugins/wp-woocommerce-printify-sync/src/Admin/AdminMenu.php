<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminMenu
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenuItems']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMenuItems(): void
    {
        // Add FontAwesome to Admin
        add_action('admin_head', function() {
            echo '<style>
                #adminmenu #toplevel_page_printify-sync .wp-menu-image::before {
                    font-family: "Font Awesome 5 Free";
                    content: "\f553"; /* fa-tshirt unicode */
                    font-weight: 900;
                }
            </style>';
        });

        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'printify-sync',
            [$this, 'renderDashboard'],
            '', // Empty string as we're using FontAwesome
            56
        );

        // Add submenu items
        add_submenu_page(
            'printify-sync',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'printify-sync',
            [$this, 'renderDashboard']
        );

        // Other submenu items...
    }

    public function enqueueAssets(): void
    {
        // Ensure FontAwesome is loaded
        wp_enqueue_style(
            'fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            [],
            '5.15.4'
        );

        // Other assets...
    }
}