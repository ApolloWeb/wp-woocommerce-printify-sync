<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface TicketManagerInterface {
    public function getTickets(array $args = []): array;
    public function createTicket(array $data): int;
    public function updateTicket(int $ticket_id, array $data): bool;
    public function getTicket(int $ticket_id): ?array;
    public function getPendingCount(): int;
}
