<?php
/**
 * Order Status Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class OrderStatusHelper {
    /**
     * Register custom order statuses
     */
    public static function registerOrderStatuses() {
        register_post_status('wc-printify-processing', [
            'label' => _x('Printify Processing', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Processing <span class="count">(%s)</span>', 'Printify Processing <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-printify-printed', [
            'label' => _x('Printify Printed', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Printed <span class="count">(%s)</span>', 'Printify Printed <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-printify-shipped', [
            'label' => _x('Printify Shipped', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Printify Shipped <span class="count">(%s)</span>', 'Printify Shipped <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-reprint-requested', [
            'label' => _x('Reprint Requested', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Reprint Requested <span class="count">(%s)</span>', 'Reprint Requested <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
        
        register_post_status('wc-refund-requested', [
            'label' => _x('Refund Requested', 'Order status', 'wp-woocommerce-printify-sync'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Refund Requested <span class="count">(%s)</span>', 'Refund Requested <span class="count">(%s)</span>', 'wp-woocommerce-printify-sync')
        ]);
    }
}