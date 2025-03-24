<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class EmailService
{
    public function __construct()
    {
        // Register custom table
        add_action('plugins_loaded', [$this, 'registerTable']);
        
        // Schedule email processing
        if (!wp_next_scheduled('wpwps_process_email_queue')) {
            wp_schedule_event(time(), 'five_minutes', 'wpwps_process_email_queue');
        }
        add_action('wpwps_process_email_queue', [$this, 'processEmailQueue']);
        
        // Register custom cron schedule
        add_filter('cron_schedules', [$this, 'addCustomCronSchedules']);
    }
    
    public function registerTable()
    {
        global $wpdb;
        $wpdb->wpwps_email_queue = $wpdb->prefix . 'wpwps_email_queue';
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->wpwps_email_queue;
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            to_email varchar(100) NOT NULL,
            subject varchar(255) NOT NULL,
            message longtext NOT NULL,
            headers text,
            attachments text,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            sent_at datetime DEFAULT NULL,
            retry_count int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function addCustomCronSchedules($schedules)
    {
        $schedules['five_minutes'] = [
            'interval' => 300, // 5 minutes in seconds
            'display' => __('Every 5 Minutes', 'wp-woocommerce-printify-sync'),
        ];
        
        return $schedules;
    }
    
    public function queueEmail($to, $subject, $message, $headers = '', $attachments = [])
    {
        global $wpdb;
        
        if (is_array($headers)) {
            $headers = implode("\r\n", $headers);
        }
        
        if (is_array($attachments) && !empty($attachments)) {
            $attachments = serialize($attachments);
        } else {
            $attachments = '';
        }
        
        $wpdb->insert(
            $wpdb->wpwps_email_queue,
            [
                'to_email' => $to,
                'subject' => $subject,
                'message' => $message,
                'headers' => $headers,
                'attachments' => $attachments,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
        
        return $wpdb->insert_id;
    }
    
    public function processEmailQueue()
    {
        global $wpdb;
        
        // Get pending emails with retry count < 3
        $emails = $wpdb->get_results(
            "SELECT * FROM {$wpdb->wpwps_email_queue} 
             WHERE status = 'pending' 
             AND retry_count < 3 
             ORDER BY created_at ASC 
             LIMIT 20"
        );
        
        if (empty($emails)) {
            return;
        }
        
        foreach ($emails as $email) {
            $attachments = !empty($email->attachments) ? unserialize($email->attachments) : [];
            
            $sent = wp_mail(
                $email->to_email,
                $email->subject,
                $email->message,
                $email->headers,
                $attachments
            );
            
            if ($sent) {
                $wpdb->update(
                    $wpdb->wpwps_email_queue,
                    [
                        'status' => 'sent',
                        'sent_at' => current_time('mysql'),
                    ],
                    ['id' => $email->id],
                    ['%s', '%s'],
                    ['%d']
                );
            } else {
                $wpdb->update(
                    $wpdb->wpwps_email_queue,
                    [
                        'retry_count' => $email->retry_count + 1,
                        'status' => $email->retry_count >= 2 ? 'failed' : 'pending',
                    ],
                    ['id' => $email->id],
                    ['%d', '%s'],
                    ['%d']
                );
            }
            
            // Short pause to prevent overwhelming mail server
            sleep(1);
        }
    }
    
    public function getPendingEmailCount()
    {
        global $wpdb;
        
        return $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->wpwps_email_queue} WHERE status = 'pending'"
        );
    }
    
    public function getFailedEmailCount()
    {
        global $wpdb;
        
        return $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->wpwps_email_queue} WHERE status = 'failed'"
        );
    }
    
    public function getEmailStats()
    {
        global $wpdb;
        
        $stats = [
            'pending' => 0,
            'sent' => 0,
            'failed' => 0,
        ];
        
        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$wpdb->wpwps_email_queue} GROUP BY status"
        );
        
        if (!empty($results)) {
            foreach ($results as $result) {
                $stats[$result->status] = $result->count;
            }
        }
        
        return $stats;
    }
}
