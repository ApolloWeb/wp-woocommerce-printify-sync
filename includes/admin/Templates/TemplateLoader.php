<?php
/**
 * Template Loader
 *
 * Handles loading and rendering admin page templates with the Bootstrap theme.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Templates
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Templates;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class TemplateLoader {
    /**
     * Singleton instance
     *
     * @var TemplateLoader
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return TemplateLoader
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
        // Initialize template hooks
        $this->init();
    }
    
    /**
     * Initialize template hooks
     */
    public function init() {
        // Register template hooks for each page type
        add_action('wpwprintifysync_render_dashboard_page', array($this, 'render_dashboard'), 10, 1);
        add_action('wpwprintifysync_render_products_page', array($this, 'render_products'), 10, 1);
        add_action('wpwprintifysync_render_orders_page', array($this, 'render_orders'), 10, 1);
        add_action('wpwprintifysync_render_tickets_page', array($this, 'render_tickets'), 10, 1);
        add_action('wpwprintifysync_render_emails_page', array($this, 'render_emails'), 10, 1);
        add_action('wpwprintifysync_render_settings_page', array($this, 'render_settings'), 10, 1);
        add_action('wpwprintifysync_render_tools_page', array($this, 'render_tools'), 10, 1);
        add_action('wpwprintifysync_render_reports_page', array($this, 'render_reports'), 10, 1);
        
        // Register partial templates
        add_action('wpwprintifysync_render_card', array($this, 'render_card'), 10, 3);
        add_action('wpwprintifysync_render_table', array($this, 'render_table'), 10, 2);
        add_action('wpwprintifysync_render_alert', array($this, 'render_alert'), 10, 3);
        add_action('wpwprintifysync_render_modal', array($this, 'render_modal'), 10, 3);
    }
    
    /**
     * Get template part
     *
     * @param string $template Template name
     * @param array $args Template arguments
     * @return void
     */
    public function get_template_part($template, $args = array()) {
        // Extract arguments to variables
        if (!empty($args) && is_array($args)) {
            extract($args);
        }
        
        // Look for template in theme first
        $template_path = get_stylesheet_directory() . '/wpwprintifysync/admin/' . $template . '.php';
        
        if (!file_exists($template_path)) {
            // Fall back to plugin template
            $template_path = WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/admin/' . $template . '.php';
        }
        
        if (file_exists($template_path)) {
            include $template_path;
        }
    }
    
    /**
     * Render dashboard page
     *
     * @param array $args Template arguments
     */
    public function render_dashboard($args = array()) {
        echo '<div class="container-fluid px-4 py-4">';
        
        // Sales overview
        echo '<div class="row mb-4">';
        echo '<div class="col-12">';
        $this->get_template_part('dashboard/sales-overview', $args);
        echo '</div>';
        echo '</div>';
        
        // Quick stats
        echo '<div class="row mb-4">';
        
        echo '<div class="col-12 col-md-6 col-xl-3 mb-4 mb-xl-0">';
        $this->get_template_part('dashboard/stat-card', array_merge($args, array(
            'title' => __('Total Products', 'wp-woocommerce-printify-sync'),
            'value' => $args['stats']['products_count'],
            'icon' => 'fa-box',
            'color' => 'primary',
            'link' => admin_url('admin.php?page=wpwprintifysync-products'),
        )));
        echo '</div>';
        
        echo '<div class="col-12 col-md-6 col-xl-3 mb-4 mb-xl-0">';
        $this->get_template_part('dashboard/stat-card', array_merge($args, array(
            'title' => __('Processing Orders', 'wp-woocommerce-printify-sync'),
            'value' => $args['stats']['processing_orders'],
            'icon' => 'fa-spinner',
            'color' => 'warning',
            'link' => admin_url('admin.php?page=wpwprintifysync-orders&status=processing'),
        )));
        echo '</div>';
        
        echo '<div class="col-12 col-md-6 col-xl-3 mb-4 mb-md-0">';
        $this->get_template_part('dashboard/stat-card', array_merge($args, array(
            'title' => __('Open Tickets', 'wp-woocommerce-printify-sync'),
            'value' => $args['stats']['open_tickets'],
            'icon' => 'fa-ticket-alt',
            'color' => 'info',
            'link' => admin_url('admin.php?page=wpwprintifysync-tickets&status=open'),
        )));
        echo '</div>';
        
        echo '<div class="col-12 col-md-6 col-xl-3">';
        $this->get_template_part('dashboard/stat-card', array_merge($args, array(
            'title' => __('Revenue (30 days)', 'wp-woocommerce-printify-sync'),
            'value' => $args['stats']['revenue_formatted'],
            'icon' => 'fa-dollar-sign',
            'color' => 'success',
            'link' => admin_url('admin.php?page=wpwprintifysync-reports&tab=sales'),
        )));
        echo '</div>';
        
        echo '</div>';
        
        // Recent activity and alerts
        echo '<div class="row">';
        
        echo '<div class="col-12 col-lg-8 mb-4 mb-lg-0">';
        $this->get_template_part('dashboard/recent-activity', $args);
        echo '</div>';
        
        echo '<div class="col-12 col-lg-4">';
        $this->get_template_part('dashboard/system-status', $args);
        echo '</div>';
        
        echo '</div>';
        
        echo '</div>'; // .container-fluid
    }
    
    /**
     * Render products page
     *
     * @param array $args Template arguments
     */
    public function render_products($args = array()) {
        // Determine which view to render
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        echo '<div class="container-fluid px-4 py-4">';
        
        // Render the appropriate template based on view and action
        switch ($view) {
            case 'blueprints':
                $this->get_template_part('products/blueprints', $args);
                break;
                
            case 'providers':
                $this->get_template_part('products/providers', $args);
                break;
                
            default:
                switch ($action) {
                    case 'new':
                    case 'edit':
                        $this->get_template_part('products/edit', $args);
                        break;
                        
                    case 'import':
                        $this->get_template_part('products/import', $args);
                        break;
                        
                    default:
                        $this->get_template_part('products/list', $args);
                        break;
                }
                break;
        }
        
        echo '</div>'; // .container-fluid
    }
    
    /**
     * Render orders page
     *
     * @param array $args Template arguments
     */
    public function render_orders($args = array()) {
        // Similar structure to render_products
        // Implement based on your specific order views
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        
        echo '<div class="container-fluid px-4 py-4">';
        
        if ($order_id > 0) {
            $this->get_template_part('orders/detail', array_merge($args, array('order_id' => $order_id)));
        } else {
            switch ($action) {
                case 'bulk':
                    $this->get_template_part('orders/bulk-actions', $args);
                    break;
                    
                default:
                    $this->get_template_part('orders/list', $args);
                    break;
            }
        }
        
        echo '</div>'; // .container-fluid
    }
    
    /**
     * Render tickets page
     *
     * @param array $args Template arguments
     */
    public function render_tickets($args = array()) {
        // Similar structure to other page renderers
        // Implement based on your specific ticket views
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $ticket_id = isset($_GET['ticket']) ? intval($_GET['ticket']) : 0;
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';
        
        echo '<div class="container-fluid px-4 py-4">';
        
        if ($ticket_id > 0) {
            $this->get_template_part('tickets/detail', array_merge($args, array('ticket_id' => $ticket_id)));
        } else if ($action === 'new') {
            $this->get_template_part('tickets/new', $args);
        } else if ($view === 'categories') {
            $this->get_template_part('tickets/categories', $args);
        } else if ($view === 'responses') {
            $this->get_template_part('tickets/responses', $args);
        } else {
            $this->get_template_part('tickets/list', $args);
        }
        
        echo '</div>'; // .container-fluid
    }
    
    /**
     * Render emails page
     *
     * @param array $args Template arguments
     */
    public function render_emails($args = array()) {
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';
        
        echo '<div class="container-fluid px-4 py-4">';
        
        switch ($view) {
            case 'templates':
                $this->get_template_part('emails/templates', $args);
                break;
                
            case 'queue':
                $this->get_template_part('emails/queue', $args);
                break;
                
            case 'log':
                $this->get_template_part('emails/log', $args);
                break;
                
            default:
                $this->get_template_part('emails/configuration', $args);
                break;
        }
        
        echo '</div>'; // .container-fluid
    }
    
    /**
     * Render settings page
     *
     * @param array $args Template arguments
     */
    public function render_settings($args = array()) {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        echo '<div class="container-fluid px-4 py-4">';
        
        $this->get_template_part('settings/tabs', array_merge($args, array('current_tab' => $tab)));
        
        switch ($tab) {
            case 'api':
                $this->get_template_part('settings/api', $args);
                break;
                
            case 'sync':
                $this->get_template_part('settings/sync', $args);
                break;
                
            case 'shipping':
                $this->get_template_part('settings/shipping', $args);
                break;
                
            case 'notifications':
                $this->get_template_part('settings/notifications', $args);
                break;
                
            case 'advanced':
                $this->get_template_part('settings/advanced', $args);
                break;
                
            case 'webhooks':
                $this->get_template_part('settings/webhooks', $args);
                break;
                
            default:
                $this->get_template_part('settings/general', $args);
                break;
        }
        
        echo '</div>'; // .container-fluid
    }
    
    /**
     * Render tools page
     *
     * @param array $args Template arguments
     */
    public function render_tools($args = array()) {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'import-export';
        
        echo '<div class="container-fluid px-4 py-4">';
        
        $this->get_template_part('tools/tabs', array_merge($args, array('current_tab' => $tab)));
        
        switch ($tab) {
            case 'system-status':
                $this->get_template_part('tools/system-status', $args);
                break;
                
            case 'api-tester':
                $this->get_template_part('tools/api-tester', $args);
                break;
                
            case 'logs':
                $this->get_template_part('tools/logs', $args);
                break;
                
            case 'cache':
                $this->get_template_part('tools/cache', $args);
                break;
                
            case 'database':
                $this->get_template_part('tools/database', $args);
                break;
                
            default:
                $this->get_template_part('tools/import-export', $args);
                break;
        }
        
        echo '</div>'; // .container-fluid
    }
    
    /**
     * Render reports page
     *
     * @param array $args Template arguments
     */
    public function render_reports($args = array()) {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'sales';
        
        echo '<div class="container-fluid px-4 py-4">';
        
        $this->get_template_part('reports/tabs', array_merge($args, array('current_tab' => $tab