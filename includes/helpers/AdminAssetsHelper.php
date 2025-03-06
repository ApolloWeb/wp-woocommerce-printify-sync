<?php
/**
 * Admin Assets Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class AdminAssetsHelper {
    /**
     * Enqueue admin assets
     */
    public static function enqueueAdminAssets($hook) {
        if (strpos($hook, 'wpwprintifysync') !== false) {
            wp_enqueue_style(
                'wpwprintifysync-admin', 
                WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/css/admin.css',
                [], 
                CoreHelper::VERSION
            );
            
            wp_enqueue_script(
                'wpwprintifysync-admin',
                WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                CoreHelper::VERSION,
                true
            );
            
            // Add localized script data
            wp_localize_script(
                'wpwprintifysync-admin',
                'wpwprintifysync',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wpwprintifysync-admin'),
                    'i18n' => [
                        'loading' => __('Loading...', 'wp-woocommerce-printify-sync'),
                        'error' => __('Error', 'wp-woocommerce-printify-sync'),
                        'success' => __('Success', 'wp-woocommerce-printify-sync'),
                        'confirm' => __('Are you sure?', 'wp-woocommerce-printify-sync'),
                    ],
                ]
            );
        }
    }
}