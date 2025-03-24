<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support\Interfaces;

interface TicketRepositoryInterface {
    public function find(int $id): ?object;
    public function findByCustomer(int $customer_id): array;
    public function save(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
