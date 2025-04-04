<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Support;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\TicketManagerInterface;

class TicketManager implements TicketManagerInterface {
    private $table_name;

    public function __construct() 
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpwps_support_tickets';
    }

    public function getTickets(array $args = []): array 
    {
        global $wpdb;
        
        $defaults = [
            'per_page' => 10,
            'page' => 1,
            'status' => 'all'
        ];

        $args = wp_parse_args($args, $defaults);
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $query = "SELECT * FROM {$this->table_name}";
        if ($args['status'] !== 'all') {
            $query .= $wpdb->prepare(" WHERE status = %s", $args['status']);
        }
        $query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $args['per_page'], $offset),
            ARRAY_A
        );
    }

    public function createTicket(array $data): int 
    {
        global $wpdb;
        
        $wpdb->insert($this->table_name, [
            'subject' => $data['subject'],
            'description' => $data['description'],
            'status' => 'open',
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);

        return $wpdb->insert_id;
    }

    public function updateTicket(int $ticket_id, array $data): bool 
    {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $ticket_id]
        ) !== false;
    }

    public function getTicket(int $ticket_id): ?array 
    {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $ticket_id
            ),
            ARRAY_A
        );
    }

    public function getPendingCount(): int 
    {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s",
                'pending'
            )
        );
    }
}
