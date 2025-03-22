<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Tickets;

use ApolloWeb\WPWooCommercePrintifySync\Tickets\Models\Ticket;

class TicketRepository {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpwps_tickets';
    }

    public function create(Ticket $ticket): int {
        global $wpdb;

        $data = $ticket->toArray();
        unset($data['id']);

        $wpdb->insert(
            $this->table_name,
            $data,
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );

        return $wpdb->insert_id;
    }

    public function update(Ticket $ticket): bool {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            $ticket->toArray(),
            ['id' => $ticket->getId()],
            ['%s', '%s', '%s', '%d', '%s', '%s'],
            ['%d']
        );
    }

    public function find($id): ?Ticket {
        global $wpdb;

        $data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );

        return $data ? new Ticket($data) : null;
    }
}
