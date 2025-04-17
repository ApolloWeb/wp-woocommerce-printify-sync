<?php
/**
 * Admin Assets Registration
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class Assets {
    /**
     * Initialize assets
     */
    public function init() {
        add_action( 'admin_enqueue_scripts', [ $this, 'registerAssets' ] );
    }
    
    /**
     * Register and enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function registerAssets( $hook ) {
        // Only load on our plugin pages
        if ( strpos( $hook, 'wpwps-' ) === false ) {
            return;
        }
        
        // Register styles
        wp_register_style(
            'wpwps-bootstrap',
            WPWPS_URL . 'assets/css/bootstrap.min.css',
            [],
            '5.3.0'
        );
        
        wp_register_style(
            'wpwps-fontawesome',
            WPWPS_URL . 'assets/css/fontawesome.min.css',
            [],
            '6.4.2'
        );
        
        wp_register_style(
            'wpwps-admin',
            WPWPS_URL . 'assets/css/wpwps-admin.css',
            ['wpwps-bootstrap', 'wpwps-fontawesome'],
            WPWPS_VERSION
        );
        
        // Register scripts
        wp_register_script(
            'wpwps-bootstrap',
            WPWPS_URL . 'assets/js/bootstrap.bundle.min.js',
            ['jquery'],
            '5.3.0',
            true
        );
        
        wp_register_script(
            'wpwps-chartjs',
            WPWPS_URL . 'assets/js/chart.min.js',
            [],
            '4.3.0',
            true
        );
        
        // Page specific scripts
        $page = str_replace('wpwps-', '', $hook);
        
        if ( file_exists( WPWPS_PATH . "assets/js/wpwps-{$page}.js" ) ) {
            wp_register_script(
                "wpwps-{$page}",
                WPWPS_URL . "assets/js/wpwps-{$page}.js",
                ['jquery', 'wpwps-bootstrap'],
                WPWPS_VERSION,
                true
            );
            
            wp_enqueue_script( "wpwps-{$page}" );
            
            // Add localization data
            wp_localize_script( "wpwps-{$page}", 'wpwps', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'wpwps_admin_nonce' ),
                'i18n' => [
                    'saving' => __( 'Saving...', 'wp-woocommerce-printify-sync' ),
                    'saved' => __( 'Saved!', 'wp-woocommerce-printify-sync' ),
                    'error' => __( 'Error', 'wp-woocommerce-printify-sync' ),
                    'confirm_import' => __( 'Are you sure you want to import all products? This may take some time.', 'wp-woocommerce-printify-sync' ),
                ]
            ]);
        }
        
        // Enqueue common styles
        wp_enqueue_style( 'wpwps-admin' );
        wp_enqueue_script( 'wpwps-bootstrap' );
        
        // Enqueue Chart.js only when needed
        if ( $hook === 'toplevel_page_wpwps-dashboard' || $hook === 'printify-sync_page_wpwps-dashboard' ) {
            wp_enqueue_script( 'wpwps-chartjs' );
        }
    }
}
