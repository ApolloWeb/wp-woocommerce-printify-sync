<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support\Interfaces;

interface ThreadManagerInterface {
    public function getThreadsForTicket(int $ticket_id): array;
    public function addThread(int $ticket_id, array $data): object;
    public function findThread(int $thread_id): ?object;
    public function updateThread(int $thread_id, array $data): bool;
}
