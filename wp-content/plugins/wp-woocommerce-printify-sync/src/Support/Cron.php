<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class Cron {
    public function register(): void {
        // Schedule email processing
        if (!wp_next_scheduled('wpwps_process_email_queue')) {
            wp_schedule_event(time(), 'every_5_minutes', 'wpwps_process_email_queue');
        }

        // Schedule email fetching
        if (!wp_next_scheduled('wpwps_fetch_support_emails')) {
            wp_schedule_event(time(), 'hourly', 'wpwps_fetch_support_emails');
        }

        // Add custom interval
        add_filter('cron_schedules', [$this, 'addIntervals']);
    }

    public function addIntervals($schedules): array {
        $schedules['every_5_minutes'] = [
            'interval' => 300,
            'display' => __('Every 5 minutes', 'wp-woocommerce-printify-sync')
        ];
        
        return $schedules;
    }
}
