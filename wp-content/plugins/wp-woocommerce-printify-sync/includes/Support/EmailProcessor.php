<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Support;

use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Core\Settings;

/**
 * Processes emails from POP3 server
 */
class EmailProcessor {
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var Settings
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct(Logger $logger, Settings $settings) {
        $this->logger = $logger;
        $this->settings = $settings;
    }
    
    /**
     * Fetch emails from POP3 server
     * 
     * @return array Array of processed emails
     */
    public function fetchEmails(): array {
        // Get POP3 settings
        $host = $this->settings->get('pop3_host', '');
        $port = $this->settings->get('pop3_port', 110);
        $username = $this->settings->get('pop3_username', '');
        $password = $this->settings->get('pop3_password', '');
        $use_ssl = $this->settings->get('pop3_use_ssl', 'yes') === 'yes';
        $delete_from_server = $this->settings->get('pop3_delete', 'yes') === 'yes';
        
        // Check if settings are configured
        if (empty($host) || empty($username) || empty($password)) {
            $this->logger->log('POP3 settings not configured', 'warning');
            return [];
        }
        
        // Check if IMAP extension is available
        if (!function_exists('imap_open')) {
            $this->logger->log('IMAP extension not available', 'error');
            return [];
        }
        
        $mailbox = null;
        $processed_emails = [];
        
        try {
            // Connect to the mailbox
            $mailbox_string = '{' . $host . ':' . $port . '/pop3' . ($use_ssl ? '/ssl' : '') . '/novalidate-cert}INBOX';
            $this->logger->log("Connecting to POP3 server: {$mailbox_string}", 'debug');
            
            // Open mailbox with retry
            $attempts = 0;
            $max_attempts = 3;
            
            while ($attempts < $max_attempts) {
                $mailbox = @imap_open($mailbox_string, $username, $password);
                
                if ($mailbox) {
                    break;
                }
                
                $this->logger->log('Failed to connect to POP3 server: ' . imap_last_error(), 'warning');
                $attempts++;
                
                if ($attempts < $max_attempts) {
                    sleep(2); // Wait before retrying
                }
            }
            
            if (!$mailbox) {
                throw new \Exception('Failed to connect to POP3 server after ' . $max_attempts . ' attempts: ' . imap_last_error());
            }
            
            $this->logger->log('Successfully connected to POP3 server', 'debug');
            
            // Get all messages
            $message_count = imap_num_msg($mailbox);
            $this->logger->log("Found {$message_count} messages in mailbox", 'debug');
            
            if ($message_count === 0) {
                imap_close($mailbox);
                return [];
            }
            
            // Process each message
            for ($i = 1; $i <= $message_count; $i++) {
                try {
                    $header = imap_headerinfo($mailbox, $i);
                    
                    // Skip messages without a from address
                    if (empty($header->from[0]->mailbox) || empty($header->from[0]->host)) {
                        $this->logger->log("Skipping message {$i} due to missing sender information", 'warning');
                        continue;
                    }
                    
                    // Get sender information
                    $from_email = $header->from[0]->mailbox . '@' . $header->from[0]->host;
                    $from_name = isset($header->from[0]->personal) ? $this->decodeHeaderString($header->from[0]->personal) : '';
                    
                    // Get subject
                    $subject = $this->decodeHeaderString($header->subject ?? '');
                    
                    // Get message ID, references, and in-reply-to for threading
                    $header_data = imap_fetchheader($mailbox, $i);
                    $message_id = $this->extractHeader($header_data, 'Message-ID');
                    $references = $this->extractHeader($header_data, 'References');
                    $in_reply_to = $this->extractHeader($header_data, 'In-Reply-To');
                    
                    // Get message structure
                    $structure = imap_fetchstructure($mailbox, $i);
                    
                    // Get message body
                    $body = $this->getMessageBody($mailbox, $i, $structure);
                    
                    // Get attachments
                    $attachments = $this->getAttachments($mailbox, $i, $structure);
                    
                    // Add to processed emails
                    $processed_emails[] = [
                        'from_email' => $from_email,
                        'from_name' => $from_name,
                        'subject' => $subject,
                        'body' => $body,
                        'date' => date('Y-m-d H:i:s', strtotime($header->date)),
                        'message_id' => $message_id,
                        'references' => $references,
                        'in_reply_to' => $in_reply_to,
                        'attachments' => $attachments
                    ];
                    
                    $this->logger->log("Processed email {$i} from {$from_email}: {$subject}", 'debug');
                    
                    // Mark for deletion if configured
                    if ($delete_from_server) {
                        imap_delete($mailbox, $i);
                    }
                } catch (\Exception $e) {
                    $this->logger->log("Error processing message {$i}: " . $e->getMessage(), 'error');
                }
            }
            
            // Expunge deleted messages
            if ($delete_from_server) {
                imap_expunge($mailbox);
                $this->logger->log('Deleted processed messages from server', 'debug');
            }
            
            $this->logger->log("Successfully processed {$message_count} emails", 'info');
        } catch (\Exception $e) {
            $this->logger->log('Error fetching emails: ' . $e->getMessage(), 'error');
        } finally {
            // Close the mailbox connection
            if ($mailbox) {
                imap_close($mailbox);
            }
        }
        
        return $processed_emails;
    }
    
    /**
     * Get message body (HTML or plain text)
     * 
     * @param resource $mailbox Mailbox connection
     * @param int $msg_number Message number
     * @param object $structure Message structure
     * @param string $part_number Part number for multipart messages
     * @return string Message body
     */
    private function getMessageBody($mailbox, int $msg_number, $structure, string $part_number = ''): string {
        $html_body = '';
        $plain_body = '';
        
        // Check if this is a multipart message
        if (isset($structure->parts) && count($structure->parts) > 0) {
            foreach ($structure->parts as $part_index => $part) {
                $current_part_number = $part_number ? "{$part_number}." . ($part_index + 1) : ($part_index + 1);
                
                // Check if this part is text
                if ($part->type === 0) { // 0 = text
                    $part_body = $this->getPartBody($mailbox, $msg_number, $current_part_number, $part);
                    
                    // Determine if HTML or plain text
                    if (isset($part->subtype) && strtoupper($part->subtype) === 'HTML') {
                        $html_body .= $part_body;
                    } else {
                        $plain_body .= $part_body;
                    }
                } 
                // Check if this part is multipart
                elseif ($part->type === 1) { // 1 = multipart
                    $nested_body = $this->getMessageBody($mailbox, $msg_number, $part, $current_part_number);
                    
                    // Check if the nested body contains HTML
                    if (strpos($nested_body, '<html') !== false || strpos($nested_body, '<body') !== false) {
                        $html_body .= $nested_body;
                    } else {
                        $plain_body .= $nested_body;
                    }
                }
            }
        } else {
            // Not multipart - get body directly
            $body = $this->getPartBody($mailbox, $msg_number, $part_number, $structure);
            
            // Determine if HTML or plain text
            if (isset($structure->subtype) && strtoupper($structure->subtype) === 'HTML') {
                $html_body = $body;
            } else {
                $plain_body = $body;
            }
        }
        
        // Prefer HTML content if available
        if (!empty($html_body)) {
            return $html_body;
        }
        
        // Convert plain text to HTML if that's all we have
        if (!empty($plain_body)) {
            return nl2br(htmlspecialchars($plain_body));
        }
        
        return '';
    }
    
    /**
     * Get the body of a specific part
     * 
     * @param resource $mailbox Mailbox connection
     * @param int $msg_number Message number
     * @param string $part_number Part number
     * @param object $part Part structure
     * @return string Part body
     */
    private function getPartBody($mailbox, int $msg_number, string $part_number, $part): string {
        // Get raw body content
        $data = $part_number ? 
            imap_fetchbody($mailbox, $msg_number, $part_number) : 
            imap_body($mailbox, $msg_number);
        
        // Handle encoding
        if (isset($part->encoding)) {
            switch ($part->encoding) {
                case 0: // 7BIT
                case 1: // 8BIT
                    break;
                case 2: // BINARY
                    break;
                case 3: // BASE64
                    $data = base64_decode($data);
                    break;
                case 4: // QUOTED-PRINTABLE
                    $data = quoted_printable_decode($data);
                    break;
                default:
                    break;
            }
        }
        
        // Handle charset
        $charset = $this->getPartCharset($part);
        if ($charset && strtoupper($charset) !== 'UTF-8') {
            $data = @iconv($charset, 'UTF-8//IGNORE', $data);
        }
        
        return $data;
    }
    
    /**
     * Get part charset
     * 
     * @param object $part Part structure
     * @return string|null Charset or null if not found
     */
    private function getPartCharset($part): ?string {
        if (isset($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtoupper($param->attribute) === 'CHARSET') {
                    return $param->value;
                }
            }
        }
        
        if (isset($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtoupper($param->attribute) === 'CHARSET') {
                    return $param->value;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get attachments from a message
     * 
     * @param resource $mailbox Mailbox connection
     * @param int $msg_number Message number
     * @param object $structure Message structure
     * @param string $part_number Part number for multipart messages
     * @return array Attachments
     */
    private function getAttachments($mailbox, int $msg_number, $structure, string $part_number = ''): array {
        $attachments = [];
        
        // Check if this is a multipart message
        if (isset($structure->parts) && count($structure->parts) > 0) {
            foreach ($structure->parts as $part_index => $part) {
                $current_part_number = $part_number ? "{$part_number}." . ($part_index + 1) : ($part_index + 1);
                
                // Check for attachment disposition or name parameter (some emails use name instead of filename)
                $is_attachment = false;
                $filename = '';
                
                // Check disposition
                if (isset($part->disposition) && strtoupper($part->disposition) === 'ATTACHMENT') {
                    $is_attachment = true;
                }
                
                // Get filename from dparameters (disposition parameters)
                if (isset($part->dparameters) && is_array($part->dparameters)) {
                    foreach ($part->dparameters as $param) {
                        if (strtoupper($param->attribute) === 'FILENAME' || strtoupper($param->attribute) === 'NAME') {
                            $filename = $this->decodeHeaderString($param->value);
                            $is_attachment = true;
                            break;
                        }
                    }
                }
                
                // If not found in dparameters, try parameters
                if (empty($filename) && isset($part->parameters) && is_array($part->parameters)) {
                    foreach ($part->parameters as $param) {
                        if (strtoupper($param->attribute) === 'NAME') {
                            $filename = $this->decodeHeaderString($param->value);
                            $is_attachment = true;
                            break;
                        }
                    }
                }
                
                // Process attachment
                if ($is_attachment && !empty($filename)) {
                    $attachment_data = imap_fetchbody($mailbox, $msg_number, $current_part_number);
                    
                    // Handle encoding
                    if (isset($part->encoding)) {
                        switch ($part->encoding) {
                            case 3: // BASE64
                                $attachment_data = base64_decode($attachment_data);
                                break;
                            case 4: // QUOTED-PRINTABLE
                                $attachment_data = quoted_printable_decode($attachment_data);
                                break;
                        }
                    }
                    
                    // Determine MIME type
                    $mime_type = 'application/octet-stream'; // Default
                    if (isset($part->subtype)) {
                        $mime_type = strtolower($part->type) . '/' . strtolower($part->subtype);
                        
                        // Convert numeric type to text
                        switch ((int)$part->type) {
                            case 0: $mime_type = 'text/' . strtolower($part->subtype); break;
                            case 1: $mime_type = 'multipart/' . strtolower($part->subtype); break;
                            case 2: $mime_type = 'message/' . strtolower($part->subtype); break;
                            case 3: $mime_type = 'application/' . strtolower($part->subtype); break;
                            case 4: $mime_type = 'audio/' . strtolower($part->subtype); break;
                            case 5: $mime_type = 'image/' . strtolower($part->subtype); break;
                            case 6: $mime_type = 'video/' . strtolower($part->subtype); break;
                            case 7: $mime_type = 'other/' . strtolower($part->subtype); break;
                        }
                    }
                    
                    $attachments[] = [
                        'filename' => $filename,
                        'content' => $attachment_data,
                        'mime_type' => $mime_type
                    ];
                }
                
                // Check if this part has its own parts (nested multipart)
                if (isset($part->parts) && count($part->parts) > 0) {
                    $nested_attachments = $this->getAttachments($mailbox, $msg_number, $part, $current_part_number);
                    $attachments = array_merge($attachments, $nested_attachments);
                }
            }
        }
        
        return $attachments;
    }
    
    /**
     * Decode MIME header string
     * 
     * @param string $string Header string
     * @return string Decoded string
     */
    private function decodeHeaderString(string $string): string {
        if (empty($string)) {
            return '';
        }
        
        $decoded = '';
        $elements = imap_mime_header_decode($string);
        
        foreach ($elements as $element) {
            $charset = $element->charset === 'default' ? 'UTF-8' : $element->charset;
            $text = $element->text;
            
            if ($charset && strtoupper($charset) !== 'UTF-8') {
                $text = @iconv($charset, 'UTF-8//IGNORE', $text);
            }
            
            $decoded .= $text;
        }
        
        return $decoded;
    }
    
    /**
     * Extract a header value from header string
     * 
     * @param string $header_string Header string
     * @param string $header_name Header name to extract
     * @return string Header value
     */
    private function extractHeader(string $header_string, string $header_name): string {
        $pattern = '/^' . preg_quote($header_name) . ':\s*(.*?)(?:\r\n(?!\s)|\r\n$)/im';
        
        if (preg_match($pattern, $header_string, $matches)) {
            // Handle multi-line headers (folded)
            $value = $matches[1];
            $pattern = '/\r\n\s+/';
            $value = preg_replace($pattern, ' ', $value);
            
            return trim($value);
        }
        
        return '';
    }
}
