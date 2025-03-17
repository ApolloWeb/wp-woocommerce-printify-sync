<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class TicketingService
{
    use TimeStampTrait;

    private LoggerInterface $logger;
    private string $table;

    public function __construct(LoggerInterface $logger)
    {
        global $wpdb;
        $this->logger = $logger;
        $this->table = $wpdb->prefix . 'wpwps_tickets';
    }

    public function createTicket(array $data): int
    {
        global $wpdb;

        $defaults = [
            'status' => 'open',
            'priority' => 'normal',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'metadata' => []
        ];

        $data = wp_parse_args($data, $defaults);
        
        // Ensure metadata is JSON
        if (is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }

        $wpdb->insert(
            $this->table,
            $this->addTimeStampData($data),
            [
                '%s', // title
                '%s', // description
                '%s', // status
                '%s', // priority
                '%s', // type
                '%s', // related_entity_type
                '%s', // related_entity_id
                '%s', // metadata
                '%s', // created_at
                '%s'  // updated_at
            ]
        );

        $ticketId = $wpdb->insert_id;

        $this->logger->info('Created ticket', $this->addTimeStampData([
            'ticket_id' => $ticketId,
            'type' => $data['type'] ?? 'general'
        ]));

        return $ticketId;
    }

    public function updateTicket(int $ticketId, array $data): bool
    {
        global $wpdb;

        $data['updated_at'] = current_time('mysql');

        // Handle metadata update
        if (isset($data['metadata'])) {
            $currentMeta = $this->getTicketMeta($ticketId);
            $data['metadata'] = json_encode(
                array_merge(
                    is_array($currentMeta) ? $currentMeta : [],
                    $data['metadata']
                )
            );
        }

        $result = $wpdb->update(
            $this->table,
            $this->addTimeStampData($data),
            ['id' => $ticketId],
            ['%s'],
            ['%d']
        );

        if ($result !== false) {
            $this->logger->info('Updated ticket', $this->addTimeStampData([
                'ticket_id' => $ticketId,
                'fields' => array_keys($data)
            ]));
        }

        return $result !== false;
    }

    public function getTicket(int $ticketId): ?array
    {
        global $wpdb;

        $ticket = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE id = %d",
                $ticketId
            ),
            ARRAY_A
        );

        if ($ticket) {
            $ticket['metadata'] = json_decode($ticket['metadata'], true);
        }

        return $ticket ?: null;
    }

    public function findTicketByMeta(string $key, $value): ?array
    {
        global $wpdb;

        $tickets = $wpdb->get_results(
            "SELECT * FROM {$this->table}",
            ARRAY_A
        );

        foreach ($tickets as $ticket) {
            $metadata = json_decode($ticket['metadata'], true);
            if (isset($metadata[$key]) && $metadata[$key] === $value) {
                $ticket['metadata'] = $metadata;
                return $ticket;
            }
        }

        return null;
    }

    private function getTicketMeta(int $ticketId): array
    {
        $ticket = $this->getTicket($ticketId);
        return $ticket ? $ticket['metadata'] : [];
    }

    public function addComment(int $ticketId, string $comment, array $metadata = []): int
    {
        global $wpdb;

        $data = $this->addTimeStampData([
            'ticket_id' => $ticketId,
            'comment' => $comment,
            'metadata' => json_encode($metadata),
            'created_at' => current_time('mysql')
        ]);

        $wpdb->insert(
            $wpdb->prefix . 'wpwps_ticket_comments',
            $data,
            ['%d', '%s', '%s', '%s']
        );

        return $wpdb->insert_id;
    }

    public function getComments(int $ticketId): array
    {
        global $wpdb;

        $comments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpwps_ticket_comments WHERE ticket_id = %d ORDER BY created_at ASC",
                $ticketId
            ),
            ARRAY_A
        );

        return array_map(function($comment) {
            $comment['metadata'] = json_decode($comment['metadata'], true);
            return $comment;
        }, $comments);
    }

    public function install(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description longtext NOT NULL,
            status varchar(50) NOT NULL,
            priority varchar(50) NOT NULL,
            type varchar(50) NOT NULL,
            related_entity_type varchar(50) DEFAULT NULL,
            related_entity_id varchar(50) DEFAULT NULL,
            metadata longtext NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY type (type),
            KEY related_entity (related_entity_type, related_entity_id)
        ) $charset_collate;";

        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_ticket_comments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) unsigned NOT NULL,
            comment longtext NOT NULL,
            metadata longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}