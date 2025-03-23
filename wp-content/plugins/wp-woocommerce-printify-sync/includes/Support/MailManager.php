<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Support;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailManager {
    private $settings;
    private $logger;
    private $pop3;
    private $smtp;

    public function __construct(Settings $settings, Logger $logger) {
        $this->settings = $settings;
        $this->logger = $logger;
        $this->initializeMailers();
    }

    private function initializeMailers(): void {
        $this->smtp = new PHPMailer(true);
        $this->smtp->isSMTP();
        $this->smtp->Host = $this->settings->get('smtp_host');
        $this->smtp->SMTPAuth = true;
        $this->smtp->Username = $this->settings->get('smtp_username');
        $this->smtp->Password = $this->settings->get('smtp_password');
        $this->smtp->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->smtp->Port = $this->settings->get('smtp_port', 587);
    }

    public function fetchNewEmails(): array {
        try {
            $inbox = imap_open(
                $this->settings->get('pop3_host'),
                $this->settings->get('pop3_username'),
                $this->settings->get('pop3_password')
            );

            $emails = imap_search($inbox, 'UNSEEN');
            if (!$emails) return [];

            $messages = [];
            foreach ($emails as $email_number) {
                $messages[] = $this->processEmail($inbox, $email_number);
            }

            imap_close($inbox);
            return $messages;
        } catch (\Exception $e) {
            $this->logger->log("Mail fetch error: " . $e->getMessage(), 'error');
            return [];
        }
    }

    private function processEmail($inbox, $email_number): array {
        $header = imap_headerinfo($inbox, $email_number);
        $body = imap_fetchbody($inbox, $email_number, 1);

        return [
            'subject' => $header->subject,
            'from' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
            'date' => $header->date,
            'body' => $this->cleanEmailBody($body),
            'message_id' => $header->message_id
        ];
    }

    private function cleanEmailBody(string $body): string {
        $body = imap_qprint($body);
        $body = strip_tags($body);
        return trim($body);
    }
}
