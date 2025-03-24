<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard\Widgets;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard\AbstractWidget;

class EmailQueueWidget extends AbstractWidget
{
    protected $id = 'email_queue';
    protected $title = 'Email Queue Status';

    protected function getData(): array
    {
        global $wpdb;
        
        $pending = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue 
            WHERE status = 'pending'
        ") ?? 0;
        
        $failed = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}wpwps_email_queue 
            WHERE status = 'failed'
        ") ?? 0;

        return [
            'pending' => $pending,
            'failed' => $failed,
            'last_run' => get_option('wpwps_email_queue_last_run', ''),
        ];
    }
}
