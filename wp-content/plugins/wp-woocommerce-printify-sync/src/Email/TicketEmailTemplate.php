<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

class TicketEmailTemplate extends \WC_Email
{
    private string $currentTime = '2025-03-15 19:07:08';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        $this->id = 'wpwps_ticket';
        $this->title = __('Support Ticket', 'wp-woocommerce-printify-sync');
        $this->description = __('Support ticket emails are sent when a new ticket is created or updated.', 'wp-woocommerce-printify-sync');
        $this->template_base = WPWPS_PLUGIN_DIR . 'templates/woocommerce/';
        $this->template_html = 'emails/ticket-email.php';
        $this->template_plain = 'emails/plain/ticket-email.php';

        // Call parent constructor
        parent::__construct();
    }

    public function get_default_subject(): string
    {
        return __('[{site_title}] Support Ticket (#{ticket_id}) - {ticket_subject}', 'wp-woocommerce-printify-sync');
    }

    public function get_default_heading(): string
    {
        return __('Support Ticket #{ticket_id}', 'wp-woocommerce-printify-sync');
    }

    public function trigger(int $ticket_id, array $args = []): void
    {
        $this->setup_locale();

        if ($ticket_id) {
            $this->object = $this->get_ticket_data($ticket_id);
            $this->recipient = $this->object->customer_email;

            $this->find['ticket-id'] = '{ticket_id}';
            $this->replace['ticket-id'] = $ticket_id;

            $this->find['ticket-subject'] = '{ticket_subject}';
            $this->replace['ticket-subject'] = $this->object->subject;
        }

        if ($this->is_enabled() && $this->get_recipient()) {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }

        $this->restore_locale();
    }

    public function get_content_html(): string
    {
        return wc_get_template_html(
            $this->template_html,
            [
                'ticket' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this
            ],
            '',
            $this->template_base
        );
    }

    public function get_content_plain(): string
    {
        return wc_get_template_html(
            $this->template_plain,
            [
                'ticket' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this
            ],
            '',
            $this->template_base
        );
    }

    private function get_ticket_data(int $ticket_id): \stdClass
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpwps_tickets WHERE id = %d",
            $ticket_id
        ));
    }
}