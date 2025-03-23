<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminAssets {
    private $pages = [
        'wpwps-dashboard' => ['js' => 'wpwps-dashboard', 'css' => 'wpwps-dashboard'],
        'wpwps-products' => ['js' => 'wpwps-products', 'css' => 'wpwps-products'],
        'wpwps-orders' => ['js' => 'wpwps-orders', 'css' => 'wpwps-orders'],
        'wpwps-settings' => ['js' => 'wpwps-settings', 'css' => 'wpwps-settings'],
        'wpwps-tickets' => ['js' => 'wpwps-tickets', 'css' => 'wpwps-tickets'],
    ];

    public function init(): void {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_head', [$this, 'addMenuIcon']);
    }

    public function enqueueAssets(string $hook): void {
        // Always load core admin assets
        $this->enqueueCore();
        
        // Load page-specific assets
        $page = $_GET['page'] ?? '';
        
        if (isset($this->pages[$page])) {
            $this->enqueuePageAssets($page, $this->pages[$page]);
        }
    }

    private function enqueueCore(): void {
        // Bootstrap 5
        wp_enqueue_style(
            'wpwps-bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );
        
        // Font Awesome 6 Free
        wp_enqueue_style(
            'wpwps-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );
        
        // Chart.js
        wp_enqueue_script(
            'wpwps-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '4.3.0',
            true
        );
        
        // Core admin CSS
        wp_enqueue_style(
            'wpwps-admin',
            WPPS_URL . 'assets/admin/css/wpwps-admin.css',
            ['wpwps-bootstrap'],
            WPPS_VERSION
        );
        
        // Core admin JS
        wp_enqueue_script(
            'wpwps-admin',
            WPPS_URL . 'assets/admin/js/wpwps-admin.js',
            ['jquery', 'wp-util'],
            WPPS_VERSION,
            true
        );
        
        // Core admin localization
        wp_localize_script('wpwps-admin', 'wpwpsAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps_admin'),
            'shopId' => get_option('wpwps_shop_id'),
            'i18n' => [
                'error' => __('Error', 'wp-woocommerce-printify-sync'),
                'success' => __('Success', 'wp-woocommerce-printify-sync'),
                'confirm' => __('Are you sure?', 'wp-woocommerce-printify-sync'),
                'saving' => __('Saving...', 'wp-woocommerce-printify-sync'),
                'loading' => __('Loading...', 'wp-woocommerce-printify-sync')
            ]
        ]);
    }

    private function enqueuePageAssets(string $page, array $assets): void {
        if (!empty($assets['css'])) {
            wp_enqueue_style(
                $assets['css'],
                WPPS_URL . "assets/admin/css/{$assets['css']}.css",
                ['wpwps-admin'],
                WPPS_VERSION
            );
        }
        
        if (!empty($assets['js'])) {
            wp_enqueue_script(
                $assets['js'],
                WPPS_URL . "assets/admin/js/{$assets['js']}.js",
                ['wpwps-admin'],
                WPPS_VERSION,
                true
            );
        }
    }

    public function addMenuIcon(): void {
        ?>
        <style>
            #adminmenu .toplevel_page_wpwps-dashboard .wp-menu-image::before {
                content: '\f53f';
                font-family: 'Font Awesome 6 Free';
                font-weight: 900;
            }
        </style>
        <?php
    }
}
