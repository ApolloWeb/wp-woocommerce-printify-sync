<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class EmailThreadingService
{
    private const THREAD_META_KEY = '_email_thread_id';
    private const REFERENCES_META_KEY = '_email_references';
    private const IN_REPLY_TO_META_KEY = '_email_in_reply_to';

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function processEmailThread(array $email, int $ticketId): void
    {
        $threadId = $this->determineThreadId($email);
        if (!$threadId) {
            $threadId = $this->generateThreadId($email);
        }

        $this->saveThreadMetadata($ticketId, $email, $threadId);
        $this->updateThreadHistory($ticketId, $email);
    }

    private function determineThreadId(array $email): ?string
    {
        // Check References header first
        if (!empty($email['references'])) {
            foreach ($email['references'] as $reference) {
                $existingTicket = $this->findTicketByReference($reference);
                if ($existingTicket) {
                    return get_post_meta($existingTicket, self::THREAD_META_KEY, true);
                }
            }
        }

        // Check In-Reply-To header
        if (!empty($email['in_reply_to'])) {
            $existingTicket = $this->findTicketByReference($email['in_reply_to']);
            if ($existingTicket) {
                return get_post_meta($existingTicket, self::THREAD_META_KEY, true);
            }
        }

        return null;
    }

    private function generateThreadId(array $email): string
    {
        return md5($email['from'] . time() . wp_generate_uuid4());
    }

    private function saveThreadMetadata(int $ticketId, array $email, string $threadId): void
    {
        update_post_meta($ticketId, self::THREAD_META_KEY, $threadId);
        
        if (!empty($email['references'])) {
            update_post_meta($ticketId, self::REFERENCES_META_KEY, $email['references']);
        }
        
        if (!empty($email['in_reply_to'])) {
            update_post_meta($ticketId, self::IN_REPLY_TO_META_KEY, $email['in_reply_to']);
        }
    }

    private function updateThreadHistory(int $ticketId, array $email): void
    {
        $history = get_post_meta($ticketId, '_thread_history', true) ?: [];
        
        $history[] = [
            'timestamp' => current_time('mysql'),
            'from' => $email['from'],
            'message_id' => $email['message_id'],
            'subject' => $email['subject'],
            'type' => 'email'
        ];

        update_post_meta($ticketId, '_thread_history', $history);
    }

    private function findTicketByReference(string $reference): ?int
    {
        global $wpdb;

        $ticket = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
            WHERE (meta_key = %s AND meta_value LIKE %s)
            OR (meta_key = %s AND meta_value = %s)
            LIMIT 1",
            self::REFERENCES_META_KEY,
            '%' . $wpdb->esc_like($reference) . '%',
            self::IN_REPLY_TO_META_KEY,
            $reference
        ));

        return $ticket ? (int)$ticket : null;
    }

    public function getThreadMessages(int $ticketId): array
    {
        $threadId = get_post_meta($ticketId, self::THREAD_META_KEY, true);
        if (!$threadId) {
            return [];
        }

        global $wpdb;

        $relatedTickets = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
            WHERE meta_key = %s AND meta_value = %s",
            self::THREAD_META_KEY,
            $threadId
        ));

        $messages = [];
        foreach ($relatedTickets as $relatedId) {
            $history = get_post_meta($relatedId, '_thread_history', true) ?: [];
            $messages = array_merge($messages, $history);
        }

        // Sort by timestamp
        usort($messages, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });

        return $messages;
    }
}