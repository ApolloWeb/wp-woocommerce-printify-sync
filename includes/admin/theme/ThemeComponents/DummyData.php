<?php
/**
 * Dummy Data Generator
 *
 * Provides dummy data for the admin dashboard during development.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents
 * @version 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * DummyData class
 */
class DummyData {
    /**
     * Initialize the component
     */
    public static function init() {
        // Add AJAX handlers for dummy data
        add_action('wp_ajax_wpwprintifysync_get_dummy_sales_data', array(__CLASS__, 'get_dummy_sales_data'));
        add_action('wp_ajax_wpwprintifysync_get_dummy_products', array(__CLASS__, 'get_dummy_products'));
        add_action('wp_ajax_wpwprintifysync_get_dummy_orders', array(__CLASS__, 'get_dummy_orders'));
        add_action('wp_ajax_wpwprintifysync_get_dummy_tickets', array(__CLASS__, 'get_dummy_tickets'));
    }
    
    /**
     * Get dummy sales data for charts
     */
    public static function get_dummy_sales_data() {
        check_ajax_referer('wpwprintifysync-theme', 'nonce');
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'week';
        
        switch ($period) {
            case 'day':
                $labels = array('12 AM', '2 AM', '4 AM', '6 AM', '8 AM', '10 AM', '12 PM', '2 PM', '4 PM', '6 PM', '8 PM', '10 PM');
                $data = self::generate_random_data(12, 0, 500);
                break;
                
            case 'week':
                $labels = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
                $data = self::generate_random_data(7, 500, 2500);
                break;
                
            case 'month':
                $days_in_month = 30;
                $labels = range(1, $days_in_month);
                $data = self::generate_random_data($days_in_month, 100, 1000);
                break;
                
            case 'year':
                $labels = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
                $data = self::generate_random_data(12, 5000, 25000);
                break;
                
            default:
                $labels = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
                $data = self::generate_random_data(7, 500, 2500);
                break;
        }
        
        $response = array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Revenue', 'wp-woocommerce-printify-sync'),
                    'data' => $data,
                    'backgroundColor' => 'rgba(74, 58, 255, 0.1)',
                    'borderColor' => 'rgba(74, 58, 255, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                    'fill' => true,
                )
            ),
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Get dummy products data
     */
    public static function get_dummy_products() {
        check_ajax_referer('wpwprintifysync-theme', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        
        $products = array();
        $total = 87; // Total dummy products
        
        for ($i = (($page - 1) * $per_page) + 1; $i <= min($page * $per_page, $total); $i++) {
            $product_type = self::get_random_product_type();
            $status = self::get_random_status();
            
            $products[] = array(
                'id' => $i,
                'name' => ucfirst(self::get_random_adjective()) . ' ' . ucfirst($product_type),
                'sku' => 'PRD-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'type' => $product_type,
                'price' => number_format(rand(1999, 5999) / 100, 2),
                'status' => $status,
                'status_text' => ucfirst($status),
                'status_color' => self::get_status_color($status),
                'image' => 'https://picsum.photos/id/' . ($i % 100) . '/100/100',
                'last_synced' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' hours')),
                'edit_link' => admin_url('admin.php?page=wpwprintifysync-products&action=edit&id=' . $i),
            );
        }
        
        $response = array(
            'products' => $products,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'page' => $page,
            'per_page' => $per_page,
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Get dummy orders data
     */
    public static function get_dummy_orders() {
        check_ajax_referer('wpwprintifysync-theme', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        
        $orders = array();
        $total = 124; // Total dummy orders
        
        for ($i = (($page - 1) * $per_page) + 1; $i <= min($page * $per_page, $total); $i++) {
            $status = self::get_random_order_status();
            $date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days'));
            
            $orders[] = array(
                'id' => $i,
                'order_number' => '#' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'customer' => self::get_random_name(),
                'products' => rand(1, 5),
                'total' => number_format(rand(1999, 15999) / 100, 2),
                'status' => $status,
                'status_text' => ucfirst($status),
                'status_color' => self::get_order_status_color($status),
                'date' => $date,
                'date_formatted' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($date)),
                'tracking_number' => 'TRK' . strtoupper(substr(md5($i), 0, 12)),
                'edit_link' => admin_url('admin.php?page=wpwprintifysync-orders&order_id=' . $i),
            );
        }
        
        $response = array(
            'orders' => $orders,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'page' => $page,
            'per_page' => $per_page,
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Get dummy tickets data
     */
    public static function get_dummy_tickets() {
        check_ajax_referer('wpwprintifysync-theme', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        
        $tickets = array();
        $total = 47; // Total dummy tickets
        
        $ticket_subjects = array(
            'Issue with order fulfillment',
            'Product synchronization failed',
            'Missing product variants',
            'API connection error',
            'Shipping label not generating',
            'Product image not displaying',
            'Price discrepancy between