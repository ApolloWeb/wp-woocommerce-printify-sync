<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class EmailQueue {
    private $settings;
    
    public function __construct($settings) {
        $this->settings = $settings;
    }

    public function processQueue(): void {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpwps_email_queue';
        $pending = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY priority DESC, created_at ASC LIMIT 50"
        );

        foreach ($pending as $email) {
            $this->sendEmail($email);
        }
    }

    private function sendEmail($email): bool {
        $headers = [
            'From: ' . $this->settings->get('support_email_from'),
            'Content-Type: text/html; charset=UTF-8'
        ];

        $sent = wp_mail(
            $email->to_email,
            $email->subject,
            $this->formatEmailBody($email->body),
            $headers,
            $email->attachments ? unserialize($email->attachments) : []
        );

        $this->updateStatus($email->id, $sent ? 'sent' : 'failed');
        
        return $sent;
    }

    private function formatEmailBody($body): string {
        // Add signature
        $signature = $this->getEmailSignature();
        return $body . $signature;
    }
}
