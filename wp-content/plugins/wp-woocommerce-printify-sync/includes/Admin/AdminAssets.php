<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Admin Assets handler
 */
class AdminAssets {
    /**
     * @var array Admin pages
     */
    private $admin_pages = [
        'wpwps-dashboard',
        'wpwps-products',
        'wpwps-orders',
        'wpwps-shipping',
        'wpwps-settings',
        'wpwps-logs'
    ];
    
    /**
     * Initialize admin assets
     */
    public function init(): void {
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        
        // Add prefetch for external assets for better performance
        add_filter('style_loader_tag', [$this, 'addPreloadToStyles'], 10, 4);
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueueStyles(): void {
        // Only enqueue on plugin admin pages
        if (!$this->isPluginAdminPage()) {
            return;
        }
        
        // Bootstrap CSS
        wp_enqueue_style(
            'wpwps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );
        
        // Font Awesome
        wp_enqueue_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );
        
        // Admin CSS
        wp_enqueue_style(
            'wpwps-admin',
            WPPS_URL . 'assets/admin/css/admin.css',
            [],
            WPPS_VERSION
        );
        
        // Page-specific CSS
        $page = $this->getCurrentPage();
        if ($page) {
            wp_enqueue_style(
                "wpwps-{$page}",
                WPPS_URL . "assets/admin/css/{$page}.css",
                ['wpwps-admin'],
                WPPS_VERSION
            );
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueueScripts(): void {
        // Only enqueue on plugin admin pages
        if (!$this->isPluginAdminPage()) {
            return;
        }
        
        // jQuery
        wp_enqueue_script('jquery');
        
        // Bootstrap JS
        wp_enqueue_script(
            'wpwps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            [],
            '5.3.0',
            true
        );
        
        // Chart.js for dashboards
        wp_enqueue_script(
            'wpwps-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '4.3.0',
            true
        );
        
        // Admin JS
        wp_enqueue_script(
            'wpwps-admin',
            WPPS_URL . 'assets/admin/js/admin.js',
            ['jquery', 'wpwps-bootstrap'],
            WPPS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('wpwps-admin', 'wpwpsAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_admin'),
            'i18n' => [
                'error' => __('Error', 'wp-woocommerce-printify-sync'),
                'success' => __('Success', 'wp-woocommerce-printify-sync'),
                'confirm' => __('Are you sure?', 'wp-woocommerce-printify-sync'),
                'loading' => __('Loading...', 'wp-woocommerce-printify-sync'),
                'noResults' => __('No results found', 'wp-woocommerce-printify-sync')
            ]
        ]);
        
        // Page-specific JS
        $page = $this->getCurrentPage();
        if ($page) {
            wp_enqueue_script(
                "wpwps-{$page}",
                WPPS_URL . "assets/admin/js/{$page}.js",
                ['wpwps-admin'],
                WPPS_VERSION,
                true
            );
        }
    }
    
    /**
     * Add preload for critical assets
     *
     * @param string $html The link tag for the enqueued style.
     * @param string $handle The style's registered handle.
     * @param string $href The stylesheet's source URL.
     * @param string $media The stylesheet's media attribute.
     * @return string Modified link tag.
     */
    public function addPreloadToStyles(string $html, string $handle, string $href, string $media): string {
        if (in_array($handle, ['wpwps-bootstrap', 'wpwps-fontawesome'])) {
            $html = '<link rel="preload" as="style" href="' . $href . '" />' . $html;
        }
        return $html;
    }
    
    /**
     * Check if current page is a plugin admin page
     *
     * @return bool
     */
    private function isPluginAdminPage(): bool {
        $screen = get_current_screen();
        
        if (!$screen) {
            return false;
        }
        
        $page = $_GET['page'] ?? '';
        
        return in_array($page, $this->admin_pages);
    }
    
    /**
     * Get current page name without prefix
     *
     * @return string|null
     */
    private function getCurrentPage(): ?string {
        $page = $_GET['page'] ?? '';
        
        if (strpos($page, 'wpwps-') === 0) {
            return str_replace('wpwps-', '', $page);
        }
        
        return null;
    }
}
