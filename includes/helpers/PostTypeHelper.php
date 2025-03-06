<?php
/**
 * Post Type Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class PostTypeHelper {
    /**
     * Register custom post types
     */
    public static function registerPostTypes() {
        // Ticket post type
        register_post_type('wpws_ticket', [
            'labels' => [
                'name' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
                'singular_name' => __('Support Ticket', 'wp-woocommerce-printify-sync'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'custom-fields'],
            'capability_type' => 'post',
            'hierarchical' => false,
        ]);
    }
}