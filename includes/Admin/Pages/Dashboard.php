<?php
/**
 * Dashboard page.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Dashboard page class
 */
class Dashboard extends AbstractPage {

    /**
     * Initialize variables
     */
    protected function init() {
        $this->page_slug = 'dashboard';
        $this->template_path = '/templates/admin/dashboard.php';
    }

    /**
     * Get template variables
     *
     * @return array
     */
    protected function get_template_vars() {
        $api_connected = get_option( 'printify_sync_api_connected', false );
        $shop_data = get_option( 'printify_sync_shop_data', array() );
        
        // Get statistics
        $stats = $this->get_dashboard_stats();
        
        return array(
            'api_status' => $api_connected ? 'connected' : 'disconnected',
            'shop_data' => $shop_data,
            'stats' => $stats,
            'recent_orders' => $this->get_recent_orders(),
            'recent_tickets' => $this->get_recent_tickets(),
            'sync_status' => $this->get_sync_status(),
        );
    }
    
    /**
     * Get dashboard statistics
     *
     * @return array
     */
    private function get_dashboard_stats() {
        // Example stats - in a real implementation, you'd get these from your database or API
        return array(
            'total_products' => $this->count_synced_products(),
            'pending_sync' => $this->count_pending_sync_products(),
            'total_orders' => $this->count_printify_orders(),
            'orders_processing' => $this->count_orders_by_status('processing'),
            'orders_completed' => $this->count_orders_by_status('completed'),
            'tickets_open' => $this->count_tickets_by_status('open'),
            'currency_rate' => $this->get_current_exchange_rate(),
            'last_sync_time' => get_option('printify_sync_last_sync_time', 'Never'),
        );
    }
    
    /**
     * Count synced products
     *
     * @return int
     */
    private function count_synced_products() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                WHERE meta_key = %s",
                '_printify_product_id'
            )
        );
        
        return $count ? absint( $count ) : 0;
    }
    
    /**
     * Count pending sync products
     *
     * @return int
     */
    private function count_pending_sync_products() {
        // Implementation depends on how you're tracking pending syncs
        return 0;
    }
    
    /**
     * Count Printify orders
     *
     * @return int
     */
    private function count_printify_orders() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                WHERE meta_key = %s",
                '_printify_order_id'
            )
        );
        
        return $count ? absint( $count ) : 0;
    }
    
    /**
     * Count orders by status
     *
     * @param string $status Order status.
     * @return int
     */
    private function count_orders_by_status( $status ) {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'shop_order'
                AND p.post_status = %s
                AND pm.meta_key = %s",
                'wc-' . $status,
                '_printify_order_id'
            )
        );
        
        return $count ? absint( $count ) : 0;
    }
    
    /**
     * Count tickets by status
     *
     * @param string $status Ticket status.
     * @return int
     */
    private function count_tickets_by_status( $status ) {
        // Implementation depends on how you're tracking tickets
        return 0;
    }
    
    /**
     * Get current exchange rate
     *
     * @return string
     */
    private function get_current_exchange_rate() {
        $rate = get_option('printify_sync_currency_rate', '1.00');
        return number_format((float)$rate, 2, '.', '');
    }
    
    /**
     * Get recent orders
     *
     * @return array
     */
    private function get_recent_orders() {
        $args = array(
            'post_type'      => 'shop_order',
            'post_status'    => array_keys(wc_get_order_statuses()),
            'posts_per_page' => 5,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_printify_order_id',
                    'compare' => 'EXISTS',
                ),
            ),
        );
        
        $query = new \WP_Query($args);
        
        $orders = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $order_id = get_the_ID();
                $order = wc_get_order($order_id);
                
                if (!$order) {
                    continue;
                }
                
                $orders[] = array(
                    'id'            => $order_id,
                    'printify_id'   => get_post_meta($order_id, '_printify_order_id', true),
                    'status'        => $order->get_status(),
                    'date'          => $order->get_date_created()->date('Y-m-d H:i:s'),
                    'total'         => $order->get_formatted_order_total(),
                    'customer'      => $order->get_formatted_billing_full_name(),
                );
            }
            wp_reset_postdata();
        }
        
        return $orders;
    }
    
    /**
     * Get recent tickets
     *
     * @return array
     */
    private function get_recent_tickets() {
        // Implementation depends on how you're tracking tickets
        return array();
    }
    
    /**
     * Get sync status
     *
     * @return array
     */
    private function get_sync_status() {
        return array(
            'last_product_sync' => get_option('printify_sync_last_product_sync', 'Never'),
            'last_order_sync' => get_option('printify_sync_last_order_sync', 'Never'),
            'last_stock_sync' => get_option('printify_sync_last_stock_sync', 'Never'),
            'webhook_status' => get_option('printify_sync_webhook_status', 'Not configured'),
        );
    }

    /**
     * Handle AJAX requests
     */
    public function handle_ajax() {
        check_ajax_referer( 'printify_sync_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You don\'t have permission to do this.', 'wp-woocommerce-printify-sync' ) ) );
        }

        $action = isset( $_REQUEST['action_type'] ) ? sanitize_text_field( $_REQUEST['action_type'] ) : '';
        
        switch ( $action ) {
            case 'refresh_stats':
                wp_send_json_success( array(
                    'stats' => $this->get_dashboard_stats(),
                    'sync_status' => $this->get_sync_status(),
                ) );
                break;
                
            default:
                wp_send_json_error( array( 'message' => __( 'Invalid action.', 'wp-woocommerce-printify-sync' ) ) );
                break;
        }
    }
}