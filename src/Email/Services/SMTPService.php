<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Services;

class SMTPService {
    const PROCESS_QUEUE_ACTION = 'wpwps_process_email_queue';
    private $settings;
    private $logger;
    private $queue_table;

    public function __construct(Logger $logger) {
        global $wpdb;
        $this->queue_table = $wpdb->prefix . 'wpwps_email_queue';
        $this->logger = $logger;
        $this->settings = get_option('wpwps_smtp_settings', []);
    }

    public function init() {
        add_action('phpmailer_init', [$this, 'configureSMTP']);
        add_action(self::PROCESS_QUEUE_ACTION, [$this, 'processQueue']);
        $this->scheduleQueueProcessor();
    }

    public function configureSMTP($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host = $this->settings['host'];
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $this->settings['username'];
        $phpmailer->Password = $this->settings['password'];
        $phpmailer->SMTPSecure = $this->settings['encryption'];
        $phpmailer->Port = $this->settings['port'];
        
        return $phpmailer;
    }

    public function queueEmail($to, $subject, $body, $headers = [], $attachments = []) {
        global $wpdb;

        return $wpdb->insert(
            $this->queue_table,
            [
                'to_email' => $to,
                'subject' => $subject,
                'body' => $body,
                'headers' => serialize($headers),
                'attachments' => serialize($attachments),
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'attempts' => 0
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d']
        );
    }

    public function processQueue() {
        global $wpdb;

        $batch_size = apply_filters('wpwps_email_queue_batch_size', 20);

        $emails = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->queue_table} 
            WHERE status = 'pending' 
            AND (retry_after IS NULL OR retry_after <= %s)
            ORDER BY priority DESC, created_at ASC 
            LIMIT %d",
            current_time('mysql'),
            $batch_size
        ));

        foreach ($emails as $email) {
            try {
                $this->processEmail($email);
            } catch (\Exception $e) {
                $this->queue_monitor->handleFailedEmail($email->id, $e->getMessage());
            }
        }
    }

    private function processEmail($email) {
        add_filter('wp_mail_failed', [$this, 'captureMailError']);
        
        $sent = wp_mail(
            $email->to_email,
            $email->subject,
            $email->body,
            unserialize($email->headers),
            unserialize($email->attachments)
        );

        remove_filter('wp_mail_failed', [$this, 'captureMailError']);

        if ($sent) {
            $this->markEmailAsSent($email->id);
        } else {
            throw new \Exception('Email sending failed');
        }
    }

    public function captureMailError($error) {
        if ($error instanceof \WP_Error) {
            throw new \Exception($error->get_error_message());
        }
    }

    private function handleFailedEmail($email, $error = '') {
        global $wpdb;
        
        $wpdb->update(
            $this->queue_table,
            [
                'attempts' => $email->attempts + 1,
                'last_error' => $error,
                'status' => $email->attempts + 1 >= 3 ? 'failed' : 'pending'
            ],
            ['id' => $email->id]
        );

        $this->logger->error("Failed to send email: {$error}", [
            'to' => $email->to_email,
            'subject' => $email->subject
        ]);
    }

    private function scheduleQueueProcessor() {
        $interval = $this->settings['queue_interval'] ?? 300; // 5 minutes default
        
        if (!wp_next_scheduled(self::PROCESS_QUEUE_ACTION)) {
            wp_schedule_event(time(), $interval, self::PROCESS_QUEUE_ACTION);
        }
    }
}
