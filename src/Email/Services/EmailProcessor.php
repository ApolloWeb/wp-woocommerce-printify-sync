<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Services;

class EmailProcessor {
    private $template_loader;
    private $mailer;

    public function __construct(EmailTemplateLoader $template_loader, WC_Email $mailer) {
        $this->template_loader = $template_loader;
        $this->mailer = $mailer;
    }

    public function prepareTicketResponse($ticket, $response, $order = null) {
        $template_data = [
            'ticket_id' => $ticket->getId(),
            'subject' => $ticket->getSubject(),
            'response_content' => $response,
            'order' => $order,
            'email' => $this->mailer
        ];

        return $this->template_loader->renderEmailTemplate('ticket-response', $template_data);
    }

    public function queueEmail($to, $subject, $content, $attachments = []) {
        return do_action('wpwps_queue_email', [
            'to' => $to,
            'subject' => $subject,
            'content' => $content,
            'attachments' => $attachments
        ]);
    }
}
