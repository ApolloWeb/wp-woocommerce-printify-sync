<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Services;

class POP3Service {
    private $settings;
    private $logger;
    private $connection;

    const POLL_ACTION = 'wpwps_poll_emails';

    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->settings = get_option('wpwps_email_settings', []);
    }

    public function init() {
        add_action(self::POLL_ACTION, [$this, 'pollEmails']);
        $this->schedulePoll();
    }

    private function schedulePoll() {
        $interval = $this->settings['poll_interval'] ?? 300; // 5 minutes default
        
        if (!wp_next_scheduled(self::POLL_ACTION)) {
            wp_schedule_event(time(), $interval, self::POLL_ACTION);
        }
    }

    public function pollEmails() {
        try {
            $this->connect();
            $messages = $this->fetchMessages();
            
            foreach ($messages as $message) {
                do_action('wpwps_process_email', $message);
                $this->deleteMessage($message['uid']);
            }
            
            $this->disconnect();
        } catch (\Exception $e) {
            $this->logger->error('Email polling failed: ' . $e->getMessage());
        }
    }

    private function connect() {
        $this->connection = imap_open(
            $this->getMailboxString(),
            $this->settings['username'],
            $this->settings['password'],
            OP_SILENT
        );

        if (!$this->connection) {
            throw new \Exception('Failed to connect to mailbox: ' . imap_last_error());
        }
    }

    private function fetchMessages() {
        $messages = [];
        $emails = imap_search($this->connection, 'ALL UNSEEN');
        
        if (!$emails) {
            return $messages;
        }

        foreach ($emails as $email_number) {
            $header = imap_headerinfo($this->connection, $email_number);
            $structure = imap_fetchstructure($this->connection, $email_number);
            
            $message = [
                'uid' => imap_uid($this->connection, $email_number),
                'subject' => $this->decodeSubject($header->subject),
                'from' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
                'date' => date('Y-m-d H:i:s', strtotime($header->date)),
                'body' => $this->getMessageBody($email_number, $structure),
                'attachments' => $this->getAttachments($email_number, $structure)
            ];
            
            $messages[] = $message;
        }

        return $messages;
    }

    private function getMessageBody($email_number, $structure) {
        // Implementation for recursive MIME parsing
        // Returns both HTML and plain text versions
    }

    private function getAttachments($email_number, $structure) {
        // Implementation for attachment handling
        // Returns array of attachment metadata
    }
}
