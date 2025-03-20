<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Templates\TemplateEngine;

class AdminMenu
{
    /**
     * The template engine instance
     *
     * @var TemplateEngine
     */
    private TemplateEngine $templateEngine;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->templateEngine = new TemplateEngine();
    }
    
    /**
     * Register the menu pages
     *
     * @return void
     */
    public function registerMenuPages(): void
    {
        add_menu_page(
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            __('Printify Sync', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync',
            [$this, 'renderDashboardPage'],
            'dashicons-shirt', // Changed to shirt dashicon
            56
        );
        
        add_submenu_page(
            'printify-sync',
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            __('Dashboard', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync',
            [$this, 'renderDashboardPage']
        );
        
        add_submenu_page(
            'printify-sync',
            __('Settings', 'wp-woocommerce-printify-sync'),
            __('Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'printify-sync-settings',
            [$this, 'renderSettingsPage']
        );
    }
    
    /**
     * Render the dashboard page
     *
     * @return void
     */
    public function renderDashboardPage(): void
    {
        $settings = new Settings();
        $shopId = $settings->getShopId();
        $apiConfigured = !empty($settings->getApiKey()) && !empty($shopId);
        
        $data = [
            'shopId' => $shopId,
            'apiConfigured' => $apiConfigured,
            'settingsUrl' => admin_url('admin.php?page=printify-sync-settings'),
        ];
        
        // Pass the template engine instance so that partials can be rendered
        $this->templateEngine->render('dashboard', $data);
    }
    
    /**
     * Render the settings page
     *
     * @return void
     */
    public function renderSettingsPage(): void
    {
        $settings = new Settings();
        $data = [
            'apiKey' => $settings->getApiKey(),
            'apiEndpoint' => $settings->getApiEndpoint(),
            'shopId' => $settings->getShopId(),
        ];
        
        $this->templateEngine->render('settings/api-settings', $data);
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook The current admin page
     * @return void
     */
    public function enqueueAssets(string $hook): void
    {
        // Only load on our plugin pages
        if (strpos($hook, 'printify-sync') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'wpwps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );
        
        wp_enqueue_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );
        
        wp_enqueue_style(
            'wpwps-admin',
            WPWPS_PLUGIN_URL . 'assets/css/wpwps-admin.css',
            ['wpwps-bootstrap'],
            WPWPS_VERSION
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'wpwps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            ['jquery'],
            '5.3.0',
            true
        );
        
        wp_enqueue_script(
            'wpwps-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '4.3.0',
            true
        );
        
        // Conditional JS loading based on the page
        $current_page = $_GET['page'] ?? '';
        
        if ($current_page === 'printify-sync-settings') {
            wp_enqueue_script(
                'wpwps-settings',
                WPWPS_PLUGIN_URL . 'assets/js/wpwps-settings.js',
                ['jquery', 'wpwps-bootstrap'],
                WPWPS_VERSION,
                true
            );
        } else {
            // Dashboard scripts
            wp_enqueue_script(
                'wpwps-dashboard',
                WPWPS_PLUGIN_URL . 'assets/js/wpwps-dashboard.js',
                ['jquery', 'wpwps-bootstrap', 'wpwps-chartjs'],
                WPWPS_VERSION,
                true
            );
        }
        
        // Localize script for AJAX
        wp_localize_script('wpwps-settings', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-ajax-nonce'),
        ]);
        
        wp_localize_script('wpwps-dashboard', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-ajax-nonce'),
        ]);
    }
}
