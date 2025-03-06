<?php
/**
 * Sales Graph Widget
 *
 * Adds a filterable sales graph to the admin dashboard.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard;

use ApolloWeb\WPWooCommercePrintifySync\Orders\OrdersManager;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SalesGraph {
    /**
     * Initialize the sales graph
     */
    public function init() {
        // Add dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Add to plugin dashboard
        add_action('wpwprintifysync_dashboard_widgets', array($this, 'add_plugin_dashboard_widget'), 10);
        
        // Register scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'register_assets'));
        
        // Ajax handler for graph data
        add_action('wp_ajax_wpwprintifysync_sales_graph_data', array($this, 'ajax_get_graph_data'));
    }
    
    /**
     * Register assets
     */
    public function register_assets() {
        wp_register_script(
            'wpwprintifysync-chart-js',
            WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/vendor/chart.min.js',
            array(),
            '3.7.0',
            true
        );
        
        wp_register_script(
            'wpwprintifysync-sales-graph',
            WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/admin/sales-graph.js',
            array('jquery', 'wpwprintifysync-chart-js'),
            WPWPRINTIFYSYNC_VERSION,
            true
        );
        
        wp_localize_script('wpwprintifysync-sales-graph', 'wpwpSalesGraph', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwprintifysync-sales-graph'),
            'currency' => get_woocommerce_currency_symbol(),
            'i18n' => array(
                'sales' => __('Sales', 'wp-woocommerce-printify-sync'),
                'orders' => __('Orders', 'wp-woocommerce-printify-sync'),
                'noData' => __('No data available for the selected period', 'wp-woocommerce-printify-sync'),
            ),
        ));
        
        wp_register_style(
            'wpwprintifysync-sales-graph',
            WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/css/admin/sales-graph.css',
            array(),
            WPWPRINTIFYSYNC_VERSION
        );
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'wpwprintifysync_sales_graph',
            '<i class="fas fa-tshirt"></i> ' . __('Printify Sync Sales', 'wp-woocommerce-printify-sync'),
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Add widget to plugin dashboard
     */
    public function add_plugin_dashboard_widget() {
        echo '<div class="wpwprintifysync-dashboard-widget wpwprintifysync-dashboard-widget-sales">';
        echo '<h2 class="wpwprintifysync-dashboard-widget-title"><i class="fas fa-tshirt"></i> ' . esc_html__('Sales Overview', 'wp-woocommerce-printify-sync') . '</h2>';
        $this->render_dashboard_widget();
        echo '</div>';
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        wp_enqueue_script('wpwprintifysync-sales-graph');
        wp_enqueue_style('wpwprintifysync-sales-graph');
        
        ?>
        <div class="wpwprintifysync-sales-graph-container">
            <div class="wpwprintifysync-sales-graph-filters">
                <select id="wpwprintifysync-sales-graph-period">
                    <option value="day"><?php esc_html_e('Today', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="week" selected><?php esc_html_e('This Week', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="month"><?php esc_html_e('This Month', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="year"><?php esc_html_e('This Year', 'wp-woocommerce-printify-sync'); ?></option>
                    <option value="custom"><?php esc_html_e('Custom Range', 'wp-woocommerce-printify-sync'); ?></option>
                </select>
                
                <div id="wpwprintifysync-sales-graph-custom-range" style="display: none;">
                    <input type="date" id="wpwprintifysync-sales-graph-date-from" class="wpwprintifysync-date-input">
                    <span><?php esc_html_e('to', 'wp-woocommerce-printify-sync'); ?></span>
                    <input type="date" id="wpwprintifysync-sales-graph-date-to" class="wpwprintifysync-date-input">
                    <button id="wpwprintifysync-sales-graph-apply" class="button button-small"><?php esc_html_e('Apply', 'wp-woocommerce-printify-sync'); ?></button>
                </div>
            </div>
            
            <div class="wpwprintifysync-sales-graph-wrapper">
                <canvas id="wpwprintifysync-sales-graph" height="300"></canvas>
                <div class="wpwprintifysync-sales-graph-loading">
                    <span class="spinner is-active"></span>
                    <p><?php esc_html_e('Loading data...', 'wp-woocommerce-printify-sync'); ?></p>
                </div>
                <div class="wpwprintifysync-sales-graph-no-data" style="display: none;">
                    <p><?php esc_html_e('No sales data available for the selected period.', 'wp