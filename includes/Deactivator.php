<?php
/**
 * Fired during plugin deactivation
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

class Deactivator {
    /**
     * Plugin deactivation cleanup
     */
    public function deactivate() {
        // Remove scheduled events
        $this->clearScheduledEvents();
        
        // Optional: Clean up transients
        $this->clearTransients();
    }
    
    /**
     * Clear scheduled events
     */
    private function clearScheduledEvents() {
        $timestamp = wp_next_scheduled( 'wpwps_stock_sync' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'wpwps_stock_sync' );
        }
    }
    
    /**
     * Clear plugin transients
     */
    private function clearTransients() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '%_transient_wpwps_%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '%_transient_timeout_wpwps_%'
            )
        );
    }
}
