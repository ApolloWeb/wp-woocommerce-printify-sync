<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Fired during plugin deactivation
 */
class Deactivator {
    /**
     * Clean up scheduled events when plugin is deactivated
     */
    public static function deactivate() {
        // Remove scheduled stock sync
        $timestamp = wp_next_scheduled('wpwps_sync_stock_levels');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wpwps_sync_stock_levels');
        }
        
        // Other deactivation cleanup...
    }
}
