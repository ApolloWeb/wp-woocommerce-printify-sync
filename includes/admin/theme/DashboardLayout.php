<?php
/**
 * Dashboard Layout
 *
 * Creates the responsive dashboard layout with hamburger menu.
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

class DashboardLayout {
    /**
     * Initialize dashboard layout
     */
    public function init() {
        add_action('wpwprintifysync_admin_header', array($this, 'render_header'));
        add_action('wpwprintifysync_admin_sidebar', array($this, 'render_sidebar'));
        add_action('wpwprintifysync_admin_footer', array($this, 'render_footer'));
        add_action('wpwprintifysync_admin_notices', array($this, 'render_notices'));
    }
    
    /**
     * Render the dashboard header
     */
    public function render_header() {
        $settings = get_option('wpwprintifysync_settings', array());
        $environment_mode = isset($settings['environment_mode']) ? $settings['environment_mode'] : 'production';
        $env_class = ($environment_mode === 'development') ? 'bg-warning' : 'bg-success';
        $env_text = ($environment_mode === 'development') ? __('DEV MODE', 'wp-woocommerce-printify-sync') : __('PRODUCTION', 'wp-woocommerce-printify-sync');
        ?>
        <header class="wpwprintifysync-header navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
            <div class="container-fluid">
                <!-- Hamburger Menu Toggle -->
                <button 
                    class="navbar-toggler border-0" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#wpwprintifysyncSidebar" 
                    aria-controls="wpwprintifysyncSidebar" 
                    aria-expanded="false" 
                    aria-label="<?php esc_attr_e('Toggle navigation', 'wp-woocommerce-printify-sync'); ?>"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <!-- Brand Logo -->
                <a class="navbar-brand mx-auto mx-lg-0" href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-dashboard')); ?>">
                    <i class="fas fa-tshirt me-2 text-primary"></i>
                    <span class="fw-bold">Printify</span> <span class="text-muted">Sync</span>
                </a>
                
                <!-- Right Navigation -->
                <div class="d-flex align-items-center">
                    <!-- Environment Badge -->
                    <span class="badge <?