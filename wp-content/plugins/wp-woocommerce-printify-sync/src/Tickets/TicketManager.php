<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Tickets;

use ApolloWeb\WPWooCommercePrintifySync\API\OpenAI;

class TicketManager
{
    private OpenAI $openai;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'wpwps_tickets';
        $this->openai = new OpenAI();
    }

    public function createTicket(array $data): ?Ticket
    {
        global $wpdb;
        
        $ticket = new Ticket($data);
        $ticketData = $ticket->toArray();
        unset($ticketData['id']);
        
        $result = $wpdb->insert(
            $this->table,
            [
                'subject' => $ticketData['subject'],
                'message' => $ticketData['message'],
                'status' => $ticketData['status'],
                'order_id' => $ticketData['order_id'],
                'responses' => json_encode($ticketData['responses']),
                'user_id' => $ticketData['user_id'],
                'created_at' => $ticketData['created_at']
            ],
            ['%s', '%s', '%s', '%d', '%s', '%d', '%s']
        );

        if (!$result) {
            return null;
        }

        $ticketData['id'] = $wpdb->insert_id;
        return new Ticket($ticketData);
    }

    public function getTicket(int $id): ?Ticket
    {
        global $wpdb;
        
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        $row['responses'] = json_decode($row['responses'], true);
        return new Ticket($row);
    }

    public function updateTicket(Ticket $ticket): bool
    {
        global $wpdb;
        
        $data = $ticket->toArray();
        return (bool)$wpdb->update(
            $this->table,
            [
                'status' => $data['status'],
                'responses' => json_encode($data['responses'])
            ],
            ['id' => $data['id']],
            ['%s', '%s'],
            ['%d']
        );
    }

    public function getTickets(array $args = []): array
    {
        global $wpdb;
        
        $where = [];
        $values = [];
        
        if (isset($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if (isset($args['order_id'])) {
            $where[] = 'order_id = %d';
            $values[] = $args['order_id'];
        }

        if (isset($args['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = $args['user_id'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $query = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC";
        
        if (!empty($values)) {
            $query = $wpdb->prepare($query, ...$values);
        }

        $rows = $wpdb->get_results($query, ARRAY_A);
        return array_map(function($row) {
            $row['responses'] = json_decode($row['responses'], true);
            return new Ticket($row);
        }, $rows);
    }

    public function generateAIResponse(Ticket $ticket): ?string
    {
        $context = $this->buildTicketContext($ticket);
        return $this->openai->generateSupportResponse($context);
    }

    private function buildTicketContext(Ticket $ticket): string
    {
        $context = "Ticket Subject: {$ticket->getSubject()}\n";
        $context .= "Customer Message: {$ticket->getMessage()}\n\n";
        
        if ($ticket->getOrderId()) {
            $order = wc_get_order($ticket->getOrderId());
            if ($order) {
                $context .= "Order Information:\n";
                $context .= "- Order Status: {$order->get_status()}\n";
                $context .= "- Order Total: {$order->get_total()}\n";
                $context .= "- Products:\n";
                
                foreach ($order->get_items() as $item) {
                    $context .= "  * {$item->get_name()} (Qty: {$item->get_quantity()})\n";
                }
            }
        }

        return $context;
    }

    public static function createTable(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wpwps_tickets';
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subject varchar(255) NOT NULL,
            message text NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'open',
            order_id bigint(20) unsigned DEFAULT NULL,
            responses longtext NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY order_id (order_id),
            KEY user_id (user_id)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}