<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class EmailSystemMonitor {
    private $logger;
    private $settings;

    public function __construct($logger, $settings) {
        $this->logger = $logger;
        $this->settings = $settings;
    }

    public function init(): void {
        add_action('wp_ajax_wpwps_email_system_status', [$this, 'getSystemStatus']);
        add_action('wp_ajax_wpwps_test_email_connection', [$this, 'testConnection']);
    }

    public function getSystemStatus(): array {
        return [
            'pop3' => $this->getPOP3Status(),
            'smtp' => $this->getSMTPStatus(),
            'queue' => $this->getQueueStatus()
        ];
    }

    private function getPOP3Status(): array {
        $last_check = get_option('wpwps_last_email_check', 0);
        $messages_today = get_option('wpwps_messages_found_today', 0);

        return [
            'status' => $this->testPOP3Connection() ? 'Connected' : 'Disconnected',
            'last_check' => human_time_diff($last_check),
            'messages_today' => $messages_today
        ];
    }

    private function getSMTPStatus(): array {
        return [
            'status' => $this->testSMTPConnection() ? 'Connected' : 'Disconnected',
            'sent_today' => get_option('wpwps_emails_sent_today', 0),
            'failed_today' => get_option('wpwps_emails_failed_today', 0)
        ];
    }

    private function getQueueStatus(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'wpwps_email_queue';

        return [
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'") ?? 0,
            'processing' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'processing'") ?? 0,
            'failed' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'failed'") ?? 0,
            'next_process' => human_time_diff(wp_next_scheduled('wpwps_process_email_queue')),
            'rate' => get_option('wpwps_email_process_rate', 0)
        ];
    }

    public function enqueueAssets(): void {
        wp_enqueue_script(
            'wpwps-email-monitor',
            WPPS_URL . 'assets/admin/js/wpwps-email-monitor.js',
            ['wpwps-admin'],
            WPPS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'wpwps-email-monitor',
            WPPS_URL . 'assets/admin/css/wpwps-email-monitor.css',
            ['wpwps-admin'],
            WPPS_VERSION
        );
    }

    // ... Add other monitoring methods as needed
}
