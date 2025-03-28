<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

class LogsPage
{
    /**
     * Render the logs page.
     *
     * @return void
     */
    public function render(): void
    {
        global $wpdb;
        
        // Get logs from database
        $table = $wpdb->prefix . 'wpwps_sync_logs';
        $logs = $wpdb->get_results("SELECT * FROM {$table} ORDER BY time DESC LIMIT 100");

        // Render the logs page template
        echo View::render('wpwps-logs', [
            'logs' => $logs
        ]);
    }
}