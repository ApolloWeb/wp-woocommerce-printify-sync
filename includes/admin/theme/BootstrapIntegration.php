<?php
/**
 * Bootstrap Integration
 *
 * Loads Bootstrap assets and handles conflicts with WordPress admin.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Theme
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Theme;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BootstrapIntegration {
    /**
     * Initialize Bootstrap integration
     */
    public function init() {
        // Register scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'register_assets'));
        
        // Only load on our plugin pages
        add_action('admin_enqueue_scripts', array($this, 'load_assets'));
        
        // Add bootstrap wrapper to our pages
        add_action('wpwprintifysync_before_admin_page', array($this, 'open_bootstrap_wrapper'));
        add_action('wpwprintifysync_after_admin_page', array($this, 'close_bootstrap_wrapper'));
    }
    
    /**
     * Register Bootstrap assets
     */
    public function register_assets() {
        // Bootstrap CSS
        wp_register_style(
            'wpwprintifysync-bootstrap',
            WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );
        
        // Bootstrap Bundle JS (includes Popper)
        wp_register_script(
            'wpwprintifysync-bootstrap',
            WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.0',
            true
        );
        
        // Font Awesome
        wp_register_style(
            'wpwprintifysync-fontawesome',
            WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/css/all.min.css',
            array(),
            '6.4.0'
        );
        
        // Google Fonts
        wp_register_style(
            'wpwprintifysync-google-fonts',
            'https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&family=Roboto+Mono&display=swap',
            array(),
            '1.0.0'
        );
        
        // Custom Bootstrap theme
        wp_register_style(
            'wpwprintifysync-theme',
            WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/css/admin/theme.css',
            array('wpwprintifysync-bootstrap', 'wpwprintifysync-fontawesome', 'wpwprintifysync-google-fonts'),
            WPWPRINTIFYSYNC_VERSION
        );
        
        // Theme JavaScript
        wp_register_script(
            'wpwprintifysync-theme',
            WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/admin/theme.js',
            array('jquery', 'wpwprintifysync-bootstrap'),
            WPWPRINTIFYSYNC_VERSION,
            true
        );
    }
    
    /**
     * Load assets on plugin pages
     *
     * @param string $hook Current admin page
     */
    public function load_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'wpwprintifysync') === false) {
            return;
        }
        
        // Ensure we're not loading these on the WP dashboard widgets
        if ($hook === 'index.php' && !isset($_GET['page'])) {
            return;
        }
        
        wp_enqueue_style('wpwprintifysync-theme');
        wp_enqueue_script('wpwprintifysync-theme');
        
        // Localize script
        wp_localize_script('wpwprintifysync-theme', 'wpwprintifysyncTheme', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwprintifysync-theme'),
            'i18n' => array(
                'menuToggle' => __('Toggle Navigation', 'wp-woocommerce-printify-sync'),
                'collapse' => __('Collapse', 'wp-woocommerce-printify-sync'),
                'expand' => __('Expand', 'wp-woocommerce-printify-sync'),
            ),
        ));
    }
    
    /**
     * Open Bootstrap wrapper
     */
    public function open_bootstrap_wrapper() {
        echo '<div class="wpwprintifysync-bootstrap-wrapper">';
    }
    
    /**
     * Close Bootstrap wrapper
     */
    public function close_bootstrap_wrapper() {
        echo '</div><!-- /.wpwprintifysync-bootstrap-wrapper -->';
    }
}