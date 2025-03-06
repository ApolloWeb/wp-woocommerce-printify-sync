<?php
/**
 * Admin Theme
 *
 * Main class for the admin dashboard theme using Bootstrap.
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

/**
 * Admin Theme class
 */
class AdminTheme {
    /**
     * Singleton instance
     *
     * @var AdminTheme
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return AdminTheme
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize components
        $this->init();
    }
    
    /**
     * Initialize theme components
     */
    public function init() {
        // Register styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'register_assets'));
        
        // Apply theme to plugin pages
        add_action('admin_enqueue_scripts', array($this, 'apply_theme'));
        
        // Add body class
        add_filter('admin_body_class', array($this, 'add_body_class'));
        
        // Initialize sub-components
        ThemeComponents\Layout::init();
        ThemeComponents\Navigation::init();
        ThemeComponents\Forms::init();
        ThemeComponents\Tables::init();
        ThemeComponents\Cards::init();
        ThemeComponents\Notifications::init();
        ThemeComponents\DummyData::init();
    }
    
    /**
     * Register theme assets
     */
    public function register_assets() {
        // Bootstrap CSS
        wp_register_style(
            'wpwprintifysync-bootstrap',
            WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/css/bootstrap.min.css',
            array(),
            '5.3.0'
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
            'https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap',
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
        
        // Chart.js
        wp_register_script(
            'wpwprintifysync-chartjs',
            WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Bootstrap Bundle JS (includes Popper)
        wp_register_script(
            'wpwprintifysync-bootstrap',
            WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.0',
            true
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
     * Apply theme to plugin pages
     *
     * @param string $hook Current admin page
     */
    public function apply_theme($hook) {
        // Only apply on plugin pages
        if ($this->is_plugin_page()) {
            wp_enqueue_style('wpwprintifysync-theme');
            wp_enqueue_script('wpwprintifysync-theme');
            wp_enqueue_script('wpwprintifysync-chartjs');
            
            // Localize script
            wp_localize_script('wpwprintifysync-theme', 'wpwpTheme', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpwprintifysync-theme'),
                'dummyMode' => true,
                'i18n' => array(
                    'loading' => __('Loading...', 'wp-woocommerce-printify-sync'),
                    'noData' => __('No data available', 'wp-woocommerce-printify-sync'),
                    'confirm' => __('Are you sure?', 'wp-woocommerce-printify-sync'),
                ),
            ));
        }
    }
    
    /**
     * Add body class
     *
     * @param string $classes Body classes
     * @return string
     */
    public function add_body_class($classes) {
        if ($this->is_plugin_page()) {
            $classes .= ' wpwprintifysync-admin';
            
            // Get sidebar state
            $sidebar_collapsed = get_user_meta(get_current_user_id(), 'wpwprintifysync_sidebar_collapsed', true);
            if ($sidebar_collapsed) {
                $classes .= ' sidebar-collapsed';
            }
            
            // Get environment mode
            $settings = get_option('wpwprintifysync_settings', array());
            $environment = isset($settings['environment_mode']) ? $settings['environment_mode'] : 'production';
            $classes .= ' wpwp-env-' . $environment;
        }
        
        return $classes;
    }
    
    /**
     * Check if current page is a plugin page
     *
     * @return bool
     */
    public function is_plugin_page() {
        if (!function_exists('get_current_screen')) {
            return false;
        }
        
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }
        
        return strpos($screen->id, 'wpwprintifysync') !== false;
    }
}