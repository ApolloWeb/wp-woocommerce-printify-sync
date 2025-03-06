<?php
/**
 * Layout class
 *
 * Renders the layout components for the admin dashboard.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents
 * @version 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents;

require_once plugin_dir_path(__FILE__) . 'helpers.php';

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Layout {
    /**
     * Initialize the component
     */
    public static function init() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    /**
     * Enqueue assets
     */
    public static function enqueue_assets() {
        wp_enqueue_style('wpwprintifysync-admin-theme', plugins_url('assets/css/admin-theme.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('wpwprintifysync-admin-theme', plugins_url('assets/js/admin-theme.js', __FILE__), array('jquery', 'wp-util'), '1.0.0', true);
    }

    /**
     * Render sidebar
     */
    public static function render_sidebar() {
        ?>
        <div id="wpwprintifysync-sidebar" class="wpwprintifysync-sidebar">
            <div class="sidebar-header">
                <h3><?php esc_html_e('Admin Dashboard', 'wp-woocommerce-printify-sync'); ?></h3>
            </div>
            <div class="sidebar-menu">
                <ul class="nav flex-column">
                    <!-- Sidebar menu items -->
                    <?php echo get_sidebar_menu_items(); ?>
                </ul>
            </div>
            <div class="sidebar-footer">
                <div class="version">v<?php echo esc_html(WPWPRINTIFYSYNC_VERSION); ?></div>
                <a href="https://support.apolloweb.co" target="_blank" class="support-link" title="<?php esc_attr_e('Get Support', 'wp-woocommerce-printify-sync'); ?>">
                    <i class="fas fa-life-ring"></i>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render footer
     */
    public static function render_footer() {
        ?>
        <footer class="wpwprintifysync-footer">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        &copy; <?php echo date('Y'); ?> <?php esc_html_e('ApolloWeb', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div>
                        <?php esc_html_e('WP WooCommerce Printify Sync', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                </div>
            </div>
        </footer>
        <?php
    }
    
    /**
     * Get current page title
     *
     * @return string Page title
     */
    public static function get_current_page_title() {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';
        
        if (empty($page)) {
            return '';
        }
        
        // Base title based on page
        $title_parts = explode('-', $page);
        $base_title = end($title_parts);
        $base_title = ucfirst($base_title);
        
        // Build full title based on page, action, and view
        $title = $base_title;
        
        if ($action === 'new') {
            $title = __('Add New', 'wp-woocommerce-printify-sync') . ' ' . rtrim($base_title, 's');
        } elseif ($action === 'edit') {
            $title = __('Edit', 'wp-woocommerce-printify-sync') . ' ' . rtrim($base_title, 's');
        } elseif (!empty($view)) {
            $title = ucfirst($view) . ' ' . $base_title;
        }
        
        return $title;
    }
}