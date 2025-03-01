<?php
/**
 * Handles localization.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

class I18n {

    /**
     * Load plugin textdomain.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}