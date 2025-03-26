<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class Menu {
    public function __construct() {
        add_action('admin_menu', [$this, 'registerMenus']);
    }

    public function registerMenus(): void {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-store',
            56
        );

        $this->addSubMenuPages();
    }

    private function addSubMenuPages(): void {
        $submenus = [
            'wpwps-products' => [
                'title' => __('Products', 'wp-woocommerce-printify-sync'),
                'callback' => [$this, 'renderProducts']
            ],
            'wpwps-orders' => [
                'title' => __('Orders', 'wp-woocommerce-printify-sync'),
                'callback' => [$this, 'renderOrders']
            ],
            'wpwps-tickets' => [
                'title' => __('Tickets', 'wp-woocommerce-printify-sync'),
                'callback' => [$this, 'renderTickets']
            ],
            'wpwps-shipping' => [
                'title' => __('Shipping', 'wp-woocommerce-printify-sync'),
                'callback' => [$this, 'renderShipping']
            ],
            'wpwps-settings' => [
                'title' => __('Settings', 'wp-woocommerce-printify-sync'),
                'callback' => [$this, 'renderSettings']
            ],
        ];

        foreach ($submenus as $slug => $submenu) {
            add_submenu_page(
                'wpwps-dashboard',
                $submenu['title'],
                $submenu['title'],
                'manage_options',
                $slug,
                $submenu['callback']
            );
        }
    }

    public function renderDashboard(): void {
        $this->renderTemplate('wpwps-dashboard');
    }

    public function renderProducts(): void {
        $this->renderTemplate('wpwps-products');
    }

    public function renderOrders(): void {
        $this->renderTemplate('wpwps-orders');
    }

    public function renderTickets(): void {
        $this->renderTemplate('wpwps-tickets');
    }

    public function renderShipping(): void {
        $this->renderTemplate('wpwps-shipping');
    }

    public function renderSettings(): void {
        $this->renderTemplate('wpwps-settings');
    }

    private function renderTemplate(string $template): void {
        $template_path = WPWPS_PLUGIN_DIR . 'templates/' . $template . '.blade.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }
}