<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Deactivator {
    public function deactivate(): void {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('wpwps_sync_products');
        wp_clear_scheduled_hook('wpwps_sync_stock');
        wp_clear_scheduled_hook('wpwps_process_email_queue');
        
        // Optionally clean up options and tables
        // delete_option('wpwps_version');
    }
}
