<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface DashboardDataProviderInterface
{
    public function getTotalProducts(): int;
    public function getSyncedProducts(): int;
    public function getPendingSync(): int;
    public function getSyncErrors(): int;
    public function getRecentActivity(): array;
    public function getSyncHistory(): array;
    public function getProductCategories(): array;
    public function getLastSyncTime(): string;
}