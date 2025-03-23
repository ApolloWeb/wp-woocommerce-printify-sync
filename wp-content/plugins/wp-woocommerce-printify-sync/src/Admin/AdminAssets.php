<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminAssets {
    private const PAGES = ['dashboard', 'products', 'orders', 'settings'];

    public function init(): void {
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function enqueueStyles(): void {
        // Core styles
        wp_enqueue_style(
            'wpwps-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
        );

        wp_enqueue_style(
            'wpwps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
        );

        wp_enqueue_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
        );

        // Core admin styles
        wp_enqueue_style(
            'wpwps-admin',
            WPPS_URL . 'assets/admin/css/wpwps-admin.css',
            [],
            WPPS_VERSION
        );

        $this->maybeEnqueuePageStyles();
    }

    public function enqueueScripts(): void {
        // Core scripts
        wp_enqueue_script('jquery');
        
        wp_enqueue_script(
            'wpwps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'wpwps-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            null,
            true
        );

        // Core admin scripts
        wp_enqueue_script(
            'wpwps-admin',
            WPPS_URL . 'assets/admin/js/wpwps-admin.js',
            ['jquery', 'wpwps-bootstrap'],
            WPPS_VERSION,
            true
        );

        wp_localize_script('wpwps-admin', 'wppsAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpps_admin'),
            'i18n' => [
                'error' => __('Error', 'wp-woocommerce-printify-sync'),
                'success' => __('Success', 'wp-woocommerce-printify-sync')
            ]
        ]);

        $this->maybeEnqueuePageScripts();
    }

    private function maybeEnqueuePageStyles(): void {
        $page = $this->getCurrentPage();
        if ($page && in_array($page, self::PAGES)) {
            wp_enqueue_style(
                "wpwps-{$page}",
                WPPS_URL . "assets/admin/css/wpwps-{$page}.css",
                ['wpwps-admin'],
                WPPS_VERSION
            );
        }
    }

    private function maybeEnqueuePageScripts(): void {
        $page = $this->getCurrentPage();
        if ($page && in_array($page, self::PAGES)) {
            wp_enqueue_script(
                "wpwps-{$page}",
                WPPS_URL . "assets/admin/js/wpwps-{$page}.js",
                ['wpwps-admin'],
                WPPS_VERSION,
                true
            );
        }
    }

    private function getCurrentPage(): ?string {
        $page = $_GET['page'] ?? '';
        return strpos($page, 'wpps-') === 0 ? 
            str_replace('wpps-', '', $page) : 
            null;
    }
}
