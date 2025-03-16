<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

class EmailPreview
{
    private $email_manager;

    public function __construct(EmailManager $email_manager)
    {
        $this->email_manager = $email_manager;
        add_action('admin_menu', [$this, 'addPreviewPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_preview_email', [$this, 'handlePreviewRequest']);
    }

    public function addPreviewPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Email Template Preview', 'wp-woocommerce-printify-sync'),
            __('Email Templates', 'wp-woocommerce-printify-sync'),
            'manage_woocommerce',
            'wpwps-email-preview',
            [$this, 'renderPreviewPage']
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'woocommerce_page_wpwps-email-preview') {
            return;
        }

        wp_enqueue_style(
            'wpwps-email-preview',
            plugins_url('assets/css/email-preview.css', WPWPS_PLUGIN_FILE),
            [],
            WPWPS_VERSION
        );

        wp_enqueue_script(
            'wpwps-email-preview',
            plugins_url('assets/js/email-preview.js', WPWPS_PLUGIN_FILE),
            ['jquery'],
            WPWPS_VERSION,
            true
        );

        wp_localize_script('wpwps-email-preview', 'wpwpsEmailPreview', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-email-preview'),
            'i18n' => [
                'loading' => __('Loading preview...', 'wp-woocommerce-printify-sync'),
                'error' => __('Error loading preview', 'wp-woocommerce-printify-sync'),
            ],
        ]);
    }

    public function renderPreviewPage(): void
    {
        $email_types = [
            'ticket_created' => __('Ticket Created', 'wp-woocommerce-printify-sync'),
            'ticket_reply' => __('Ticket Reply', 'wp-woocommerce-printify-sync'),
            'ticket_status' => __('Status Change', 'wp-woocommerce-printify-sync'),
            'internal_note' => __('Internal Note', 'wp-woocommerce-printify-sync'),
        ];

        include dirname(WPWPS_PLUGIN_FILE) . '/templates/admin/email-preview.php';
    }

    public function handlePreviewRequest(): void
    {
        check_ajax_referer('wpwps-email-preview');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(-1);
        }

        $email_type = sanitize_key($_POST['email_type'] ?? '');
        $sample_data = $this->getSampleData($email_type);

        $preview = $this->email_manager->getEmailPreviewContent($email_type, $sample_data);
        
        wp_send_json_success([
            'preview' => $preview,
        ]);
    }

    private function getSampleData(string $email_type): array
    {
        $base_data = [
            'ticket_id' => 12345,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'subject' => 'Sample Support Ticket',
            'order_id' => 789,
            'created_at' => current_time('mysql'),
        ];

        switch ($email_type) {
            case 'ticket_reply':
                return array_merge($base_data, [
                    'message' => 'This is a sample reply to your support ticket.',
                    'agent_name' => 'Support Agent',
                    'original_message' => 'This is the original customer message.',
                ]);

            case 'ticket_status':
                return array_merge($base_data, [
                    'old_status' => 'open',
                    'new_status' => 'in_progress',
                    'status_note' => 'Your ticket is being processed by our support team.',
                ]);

            case 'internal_note':
                return array_merge($base_data, [
                    'content' => 'This is an internal note that has been marked for customer notification.',
                    'agent_name' => 'Support Agent',
                    'is_internal' => false,
                ]);

            case 'ticket_created':
            default:
                return array_merge($base_data, [
                    'message' => 'This is a sample support ticket message.',
                ]);
        }
    }
}