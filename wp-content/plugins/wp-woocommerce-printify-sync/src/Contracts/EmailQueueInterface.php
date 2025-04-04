<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface EmailQueueInterface {
    public function add(array $email): int;
    public function process(int $batch_size = 50): array;
    public function retry(int $email_id): bool;
    public function getStats(): array;
    public function purgeOld(int $days = 30): int;
    public function getQueuedCount(): int;
}
