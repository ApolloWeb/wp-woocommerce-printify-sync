<?php
/**
 * Theme Layout Component
 *
 * Handles the layout structure for admin pages.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Layout class
 */
class Layout {
    /**
     * Initialize the component
     */
    public static function init() {
        // Replace default WordPress admin header on plugin pages
        add_action('in_admin_header', array(__CLASS__, 'start_custom_layout'), 100);
        add_action('admin_footer', array(__CLASS__, 'end_custom_layout'), 0);
        
        // Add custom layout hooks
        add_action('wpwprintifysync_admin_header', array(__CLASS__, 'render_header'));
        add_action('wpwprintifysync_admin_sidebar', array(__CLASS__, 'render_sidebar'));
        add_action('wpwprintifysync_admin_footer', array(__CLASS__, 'render_footer'));
    }
    
    /**
     * Start custom layout
     */
    public static function start_custom_layout() {
        // Only on plugin pages
        $admin_theme = \ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\AdminTheme::get_instance();
        if (!$admin_theme->is_plugin_page()) {
            return;
        }
        
        // Start output buffering to prevent default header
        ob_start();
        
        // Open custom layout structure
        echo '<!DOCTYPE html>
        <html ' . get_language_attributes() . '>
        <head>
            ' . wp_head() . '
        </head>
        <body ' . body_class() . '>
            <div class="wpwprintifysync-app-wrapper">
                <div class="wpwprintifysync-layout">
                    ';
        
        // Render sidebar
        do_action('wpwprintifysync_admin_sidebar');
        
        // Start main content area
        echo '<div class="wpwprintifysync-main">
                <div class="wpwprintifysync-main-inner">
                    ';
        
        // Render header
        do_action('wpwprintifysync_admin_header');
        
        // Start content container
        echo '<div class="wpwprintifysync-content container-fluid px-4 py-4">';
    }
    
    /**
     * End custom layout
     */
    public static function end_custom_layout() {
        // Only on plugin pages
        $admin_theme = \ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\AdminTheme::get_instance();
        if (!$admin_theme->is_plugin_page()) {
            return;
        }
        
        // Clean previous output and end layout structure
        ob_end_clean();
        
        echo '</div>'; // Close content container
        
        // Render footer
        do_action('wpwprintifysync_admin_footer');
        
        echo '</div>
            </div>
        </div>
        </body>
        </html>';
        
        exit;
    }
    
    /**
     * Render header
     */
    public static function render_header() {
        $settings = get_option('wpwprintifysync_settings', array());
        $environment = isset($settings['environment_mode']) ? $settings['environment_mode'] : 'production';
        $env_badge_class = ($environment === 'development') ? 'bg-warning' : 'bg-success';
        $env_text = ($environment === 'development') ? __('Development', 'wp-woocommerce-printify-sync') : __('Production', 'wp-woocommerce-printify-sync');
        
        // Get current page title
        $page_title = self::get_current_page_title();
        ?>
        <header class="wpwprintifysync-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button id="sidebarToggle" class="btn btn-link text-dark me-3 d-md-none">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1 class="h3 mb-0"><?php echo esc_html($page_title); ?></h1>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge <?php echo esc_attr($env_badge_class); ?> me-3">
                            <?php echo esc_html($env_text); ?>
                        </span>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="quickActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="quickActionsDropdown">
                                <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-products&action=new')); ?>">
                                    <i class="fas fa-plus-circle me-2"></i><?php esc_html_e('New Product', 'wp-woocommerce-printify-sync'); ?>
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-orders')); ?>">
                                    <i class="fas fa-shopping-bag me-2"></i><?php esc_html_e('View Orders', 'wp-woocommerce-printify-sync'); ?>
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-settings')); ?>">
                                    <i class="fas fa-cog me-2"></i><?php esc_html_e('Settings', 'wp-woocommerce-printify-sync'); ?>
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <?php
    }
    
    /**
     * Render sidebar
     */
    public static function render_sidebar() {
        $settings = get_option('wpwprintifysync_settings', array());
        $environment = isset($settings['environment_mode']) ? $settings['environment_mode'] : 'production';
        ?>
        <div class="wpwprintifysync-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-dashboard')); ?>">
                        <i class="fas fa-tshirt"></i>
                        <span class="sidebar-brand-text">Printify Sync</span>
                    </a>
                </div>
                <button id="sidebarCollapseBtn" class="btn btn-link text-dark d-none d-md-block">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
            
            <div class="sidebar-menu">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-dashboard')); ?>" class="nav-link<?php echo self::is_current_page('dashboard') ? ' active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="nav-text"><?php esc_html_e('Dashboard', 