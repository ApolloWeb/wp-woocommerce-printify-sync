<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Services;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;

class POP3Service {
    private $settings;
    private $logger;
    private $connection;
    private $queue_manager;
    private $email_analyzer;

    const POLL_ACTION = 'wpwps_poll_emails';
    const ERROR_RETRY_LIMIT = 3;

    public function __construct(Logger $logger, QueueManager $queue_manager, EmailAnalyzer $email_analyzer) {
        $this->logger = $logger;
        $this->queue_manager = $queue_manager;
        $this->email_analyzer = $email_analyzer;
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
                $this->processEmail($message);
                $this->deleteMessage($message['uid']);
            }
            
            $this->disconnect();
            $this->logger->info(sprintf('Email polling completed: %d messages processed', count($messages)));
        } catch (\Exception $e) {
            $this->logger->error('Email polling failed: ' . $e->getMessage());
            $this->handleError($e);
        }
    }

    private function connect() {
        $mailbox = $this->getMailboxString();
        
        $this->connection = imap_open(
            $mailbox,
            $this->settings['username'],
            $this->settings['password'],
            OP_SILENT
        );

        if (!$this->connection) {
            throw new \Exception('Failed to connect to mailbox: ' . imap_last_error());
        }
        
        $this->logger->debug('Connected to mailbox: ' . $mailbox);
    }

    private function getMailboxString() {
        $port = $this->settings['port'] ?? 110;
        $encryption = $this->settings['encryption'] ?? '';
        $flags = '';
        
        if ($encryption === 'ssl') {
            $flags = '/ssl';
        } else if ($encryption === 'tls') {
            $flags = '/tls';
        }
        
        $novalidate = $this->settings['disable_cert_validation'] ? '/novalidate-cert' : '';
        
        return '{' . $this->settings['host'] . ':' . $port . '/pop3' . $flags . $novalidate . '}INBOX';
    }

    private function fetchMessages() {
        $messages = [];
        $emails = imap_search($this->connection, 'ALL UNSEEN');
        
        if (!$emails) {
            $this->logger->debug('No new emails found');
            return $messages;
        }

        foreach ($emails as $email_number) {
            try {
                $message = $this->parseMessage($email_number);
                $messages[] = $message;
            } catch (\Exception $e) {
                $this->logger->error('Error parsing email #' . $email_number . ': ' . $e->getMessage());
            }
        }

        return $messages;
    }

    private function parseMessage($email_number) {
        $header = imap_headerinfo($this->connection, $email_number);
        $structure = imap_fetchstructure($this->connection, $email_number);
        
        $message = [
            'uid' => imap_uid($this->connection, $email_number),
            'subject' => $this->decodeSubject($header->subject),
            'from' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
            'from_name' => isset($header->from[0]->personal) ? 
                $this->decodeSubject($header->from[0]->personal) : '',
            'to' => isset($header->to[0]->mailbox) ? 
                $header->to[0]->mailbox . '@' . $header->to[0]->host : '',
            'date' => date('Y-m-d H:i:s', strtotime($header->date)),
            'body' => $this->getMessageBody($email_number, $structure),
            'attachments' => $this->getAttachments($email_number, $structure),
            'message_id' => $header->message_id
        ];
        
        return $message;
    }

    private function processEmail($message) {
        try {
            // Analyze email with AI
            $analysis = $this->email_analyzer->analyzeEmail($message);
            
            // Create ticket from email
            $ticket_data = [
                'subject' => $message['subject'],
                'content' => $message['body']['html'] ?: $message['body']['plain'],
                'from_email' => $message['from'],
                'from_name' => $message['from_name'],
                'attachments' => $message['attachments'],
                'analysis' => $analysis,
                'message_id' => $message['message_id']
            ];
            
            do_action('wpwps_create_ticket_from_email', $ticket_data);
            $this->logger->info('Email processed: ' . $message['subject']);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to process email: ' . $e->getMessage());
            throw $e;
        }
    }

    private function decodeSubject($string) {
        if (!$string) return '';
        
        $decoded = imap_mime_header_decode($string);
        $result = '';
        
        foreach ($decoded as $part) {
            $charset = $part->charset === 'default' ? 'ASCII' : $part->charset;
            $result .= $this->convertEncoding($part->text, $charset);
        }
        
        return $result;
    }

    private function convertEncoding($text, $charset) {
        if ($charset === 'ASCII') {
            return $text;
        }
        
        return mb_convert_encoding($text, 'UTF-8', $charset);
    }

    private function getMessageBody($email_number, $structure) {
        $body = [
            'plain' => '',
            'html' => ''
        ];
        
        if ($structure->type === 0) {
            // This is a simple text email
            $part = imap_fetchbody($this->connection, $email_number, 1);
            $charset = $this->getPartCharset($structure);
            $decoded = $this->decodeMessagePart($part, $structure->encoding);
            
            if (strtolower($structure->subtype) === 'plain') {
                $body['plain'] = $decoded;
            } else if (strtolower($structure->subtype) === 'html') {
                $body['html'] = $decoded;
            }
        } else if ($structure->type === 1) {
            // This is a multipart email
            $this->parseMultipartBody($email_number, $structure, $body);
        }
        
        return $body;
    }
    
    private function parseMultipartBody($email_number, $structure, &$body, $partNumber = '') {
        // Recursively parse multipart emails
        $parts = $structure->parts;
        
        foreach ($parts as $index => $part) {
            $currentPartNumber = $partNumber ? $partNumber . '.' . ($index + 1) : ($index + 1);
            
            if ($part->type === 0) {
                // This is a text part
                $fetchedPart = imap_fetchbody($this->connection, $email_number, $currentPartNumber);
                $decodedPart = $this->decodeMessagePart($fetchedPart, $part->encoding);
                
                if (strtolower($part->subtype) === 'plain') {
                    $body['plain'] = $decodedPart;
                } else if (strtolower($part->subtype) === 'html') {
                    $body['html'] = $decodedPart;
                }
            } else if ($part->type === 1) {
                // This is a nested multipart
                $this->parseMultipartBody($email_number, $part, $body, $currentPartNumber);
            }
        }
    }
    
    private function decodeMessagePart($part, $encoding) {
        switch ($encoding) {
            case 0: // 7BIT
            case 1: // 8BIT
                return $part;
            case 2: // BINARY
                return $part;
            case 3: // BASE64
                return base64_decode($part);
            case 4: // QUOTED-PRINTABLE
                return quoted_printable_decode($part);
            default:
                return $part;
        }
    }
    
    private function getPartCharset($part) {
        if (isset($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute) === 'charset') {
                    return $param->value;
                }
            }
        }
        
        return 'UTF-8';
    }
    
    private function getAttachments($email_number, $structure) {
        $attachments = [];
        
        if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                $attachments = array_merge(
                    $attachments, 
                    $this->getAttachmentsRecursive($email_number, $structure->parts[$i], $i + 1)
                );
            }
        }
        
        return $attachments;
    }
    
    private function getAttachmentsRecursive($email_number, $part, $partNumber) {
        $attachments = [];
        
        if ($part->ifdisposition && strtolower($part->disposition) === 'attachment') {
            $filename = '';
            
            if ($part->ifparameters) {
                foreach ($part->parameters as $param) {
                    if (strtolower($param->attribute) === 'name') {
                        $filename = $param->value;
                    }
                }
            }
            
            if ($part->ifdparameters) {
                foreach ($part->dparameters as $param) {
                    if (strtolower($param->attribute) === 'filename') {
                        $filename = $param->value;
                    }
                }
            }
            
            $attachmentData = imap_fetchbody($this->connection, $email_number, $partNumber);
            $attachmentData = $this->decodeMessagePart($attachmentData, $part->encoding);
            
            $attachments[] = [
                'filename' => $filename,
                'data' => $attachmentData,
                'size' => strlen($attachmentData),
                'mime_type' => $part->subtype ? strtolower($part->subtype) : 'unknown'
            ];
        }
        
        if (isset($part->parts) && count($part->parts)) {
            for ($i = 0; $i < count($part->parts); $i++) {
                $attachments = array_merge(
                    $attachments,
                    $this->getAttachmentsRecursive(
                        $email_number, 
                        $part->parts[$i], 
                        $partNumber . '.' . ($i + 1)
                    )
                );
            }
        }
        
        return $attachments;
    }
    
    private function deleteMessage($uid) {
        imap_delete($this->connection, $uid, FT_UID);
        $this->logger->debug('Deleted email uid: ' . $uid);
    }
    
    private function disconnect() {
        if ($this->connection) {
            imap_expunge($this->connection);
            imap_close($this->connection);
            $this->connection = null;
            $this->logger->debug('Disconnected from mailbox');
        }
    }
    
    private function handleError(\Exception $e) {
        $error_count = get_option('wpwps_pop3_error_count', 0);
        update_option('wpwps_pop3_error_count', $error_count + 1);
        
        if ($error_count >= self::ERROR_RETRY_LIMIT) {
            $this->sendErrorNotification($e);
            update_option('wpwps_pop3_error_count', 0);
        }
    }
    
    private function sendErrorNotification(\Exception $e) {
        $admin_email = get_option('admin_email');
        $subject = sprintf('[%s] POP3 Email Polling Error', get_bloginfo('name'));
        $message = sprintf(
            "The email polling service encountered an error:\n\n%s\n\nPlease check your email settings.",
            $e->getMessage()
        );
        
        wp_mail($admin_email, $subject, $message);
        $this->logger->info('Error notification sent to admin');
    }
}
