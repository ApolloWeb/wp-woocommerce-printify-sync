<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface ChangeTrackerInterface
{
    public function trackChanges(int $productId, array $oldData, array $newData): void;
    public function getChanges(int $productId): array;
    public function hasChanges(int $productId): bool;
}