<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core\Sync\Strategies;

interface SyncStrategyInterface {
    public function supports(string $type): bool;
    public function sync(array $data): bool;
    public function validate(array $data): bool;
    public function prepare(array $data): array;
}
