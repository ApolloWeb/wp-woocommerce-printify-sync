<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class EmailHandler {
    private $pop3;
    private $openai;
    private $settings;
    
    public function __construct($settings) {
        $this->settings = $settings;
        $this->pop3 = new \Pop3($settings->get('pop3_host'), $settings->get('pop3_port'));
        $this->openai = new OpenAIService($settings->get('openai_key'));
    }

    public function processEmails(): void {
        if (!$this->pop3->connect($this->settings->get('pop3_user'), $this->settings->get('pop3_pass'))) {
            return;
        }

        $emails = $this->pop3->getEmails();
        
        foreach ($emails as $email) {
            // Process attachments
            $attachments = $this->processAttachments($email);
            
            // Extract ticket details using OpenAI
            $ticketDetails = $this->openai->analyzeEmail($email['body']);
            
            // Create or update ticket
            $this->createTicket($email, $ticketDetails, $attachments);
            
            // Remove email from server
            $this->pop3->deleteEmail($email['id']);
        }
    }

    private function processAttachments($email): array {
        $upload_dir = wp_upload_dir();
        $ticket_dir = $upload_dir['basedir'] . '/support-tickets';
        
        if (!file_exists($ticket_dir)) {
            wp_mkdir_p($ticket_dir);
        }

        $attachments = [];
        foreach ($email['attachments'] as $attachment) {
            $filename = sanitize_file_name($attachment['name']);
            $filepath = $ticket_dir . '/' . $filename;
            
            file_put_contents($filepath, $attachment['content']);
            $attachments[] = [
                'name' => $filename,
                'path' => $filepath
            ];
        }
        
        return $attachments;
    }
}
