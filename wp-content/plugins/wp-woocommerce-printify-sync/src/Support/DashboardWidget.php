<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class DashboardWidget {
    public function register(): void {
        wp_add_dashboard_widget(
            'wpwps_support_overview',
            __('Support Tickets Overview', 'wp-woocommerce-printify-sync'),
            [$this, 'render']
        );
    }

    public function render(): void {
        global $wpdb;
        
        // Get queue stats
        $queue_stats = $wpdb->get_row("
            SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed
            FROM {$wpdb->prefix}wpwps_email_queue
        ");

        // Get ticket stats
        $ticket_stats = wp_count_terms('ticket_status');

        include WPWPS_PLUGIN_DIR . 'templates/admin/dashboard-widget.php';
    }
}
