<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminMenu {
    private $templating;

    public function __construct($templating) {
        $this->templating = $templating;
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'registerMenus']);
    }

    public function registerMenus(): void {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpps-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-store',
            56
        );

        add_submenu_page(
            'wpps-dashboard',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpps-settings',
            [$this, 'renderSettings']
        );
    }

    public function renderDashboard(): void {
        echo $this->templating->render('wpwps-dashboard');
    }

    public function renderSettings(): void {
        echo $this->templating->render('wpwps-settings');
    }

    public function renderProducts(): void {
        echo $this->templating->render('wpwps-products');
    }

    public function renderOrders(): void {
        echo $this->templating->render('wpwps-orders');
    }

    public function renderShipping(): void {
        echo $this->templating->render('wpwps-shipping');
    }

    public function renderTickets(): void {
        echo $this->templating->render('wpwps-tickets');
    }
}
