<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\DashboardPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\ProductsPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\OrdersPage;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Pages\SettingsPage;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\TemplateEngineInterface;
use ApolloWeb\WPWooCommercePrintifySync\Core\Assets\Enqueue;
use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;

class AdminPages
{
    private $templateEngine;
    private $container;
    private $pages = [];
    private $enqueue;
    
    /**
     * Constructor
     *
     * @param TemplateEngineInterface $templateEngine
     * @param ServiceContainer $container
     */
    public function __construct(TemplateEngineInterface $templateEngine, ServiceContainer $container = null)
    {
        $this->templateEngine = $templateEngine;
        $this->container = $container;
        $this->enqueue = new Enqueue();
        
        // Initialize admin pages
        $this->initializePages();
    }
    
    /**
     * Initialize all admin page objects
     */
    private function initializePages()
    {
        $this->pages = [
            'dashboard' => new DashboardPage($this->templateEngine, $this->container),
            'settings' => new SettingsPage($this->templateEngine, $this->container),
            'products' => new ProductsPage($this->templateEngine, $this->container),
            'orders' => new OrdersPage($this->templateEngine, $this->container)
        ];
    }
    
    /**
     * Register all admin menus
     */
    public function registerMenus()
    {
        // Add main menu
        add_menu_page(
            'Printify Sync',                     // Page title
            'Printify Sync',                     // Menu title
            'manage_options',                    // Capability
            'wpwps-dashboard',                   // Menu slug
            [$this, 'renderPage'],              // Callback
            'none',                              // Using 'none' to use custom icon
            58                                   // Position
        );
        
        // Add custom Font Awesome icon via CSS
        add_action('admin_head', function() {
            echo '<style>
                #toplevel_page_wpwps-dashboard .wp-menu-image::before {
                    content: "\f553";
                    font-family: "Font Awesome 5 Free";
                    font-weight: 900;
                    font-size: 18px;
                }
            </style>';
        });
        
        // Add submenu pages
        foreach ($this->pages as $key => $page) {
            add_submenu_page(
                'wpwps-dashboard',              // Parent slug
                $page->pageTitle,                // Page title
                $page->menuTitle,                // Menu title
                'manage_options',                // Capability
                $page->slug,                     // Menu slug
                [$this, 'renderPage']           // Callback
            );
        }
        
        // Override the first submenu item which is duplicated by WordPress
        global $submenu;
        if (isset($submenu['wpwps-dashboard'])) {
            $submenu['wpwps-dashboard'][0][0] = 'Dashboard';
        }
    }
    
    /**
     * Render the admin page based on the current screen
     */
    public function renderPage()
    {
        $currentScreen = get_current_screen();
        $pageSlug = $currentScreen->base;
        
        // Remove the base prefix if it exists in the page slug
        $pageName = str_replace('toplevel_page_', '', $pageSlug);
        
        // For submenu pages, the format is parent_page_slug_page_page-slug
        if (strpos($pageSlug, 'printify-sync_page_') !== false) {
            $pageName = str_replace('printify-sync_page_', '', $pageSlug);
        }
        
        // Find the matching page
        foreach ($this->pages as $key => $page) {
            if ($page->slug === $pageName) {
                echo $page->render();
                break;
            }
        }
    }
    
    /**
     * Enqueue assets for admin pages
     *
     * @param string $hook The current admin page
     */
    public function enqueueAssets($hook)
    {
        // Only load assets on our plugin pages
        if (strpos($hook, 'wpwps-') === false) {
            return;
        }
        
        // Extract the page slug
        $pageSlug = str_replace('toplevel_page_', '', $hook);
        $pageSlug = str_replace('printify-sync_page_', '', $pageSlug);
        
        // Enqueue common assets
        wp_enqueue_style('wpwps-bootstrap');
        wp_enqueue_style('wpwps-fontawesome');
        wp_enqueue_script('wpwps-bootstrap');
        wp_enqueue_style('wpwps-common');
        wp_enqueue_script('wpwps-common');
        
        // Page specific assets
        foreach ($this->pages as $key => $page) {
            if ($page->slug === $pageSlug) {
                $this->enqueue->enqueuePageAssets($pageSlug);
                break;
            }
        }
        
        // Add plugin data to JavaScript
        wp_localize_script('wpwps-common', 'wpwps_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_nonce')
        ]);
    }
    
    /**
     * Get a page instance by slug
     *
     * @param string $slug The page slug
     * @return mixed The page object or null if not found
     */
    public function getPage($slug)
    {
        foreach ($this->pages as $key => $page) {
            if ($page->slug === $slug) {
                return $page;
            }
        }
        return null;
    }
}
