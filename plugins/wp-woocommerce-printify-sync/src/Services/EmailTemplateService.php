<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class EmailTemplateService
{
    public function getTemplate(string $template, array $args = []): string
    {
        // Use WooCommerce email template system
        $mailer = WC()->mailer();
        $email = new \WC_Email();
        
        // Get template path
        $template_path = $this->getTemplatePath($template);
        
        // Load template
        ob_start();
        $email->style_inline(
            wc_get_template_html(
                $template_path,
                $args,
                'wp-woocommerce-printify-sync/',
                WPWPS_PLUGIN_DIR . 'templates/'
            )
        );
        return ob_get_clean();
    }

    public function sendTicketEmail(int $ticketId, string $template, array $args = []): void
    {
        $ticket = get_post($ticketId);
        if (!$ticket) {
            return;
        }

        $customer_email = get_post_meta($ticketId, '_customer_email', true);
        if (!$customer_email) {
            return;
        }

        $args = array_merge($args, [
            'ticket' => $ticket,
            'ticket_id' => $ticketId,
            'customer_email' => $customer_email
        ]);

        $content = $this->getTemplate($template, $args);
        
        wc_mail(
            $customer_email,
            $this->getEmailSubject($template, $args),
            $content,
            $this->getEmailHeaders()
        );
    }

    private function getEmailHeaders(): array
    {
        return [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('woocommerce_email_from_name') . ' <' . get_option('woocommerce_email_from_address') . '>'
        ];
    }

    private function getEmailSubject(string $template, array $args): string
    {
        $subjects = [
            'ticket_created' => sprintf(
                __('[Ticket #%d] Ticket Created', 'wp-woocommerce-printify-sync'),
                $args['ticket_id']
            ),
            