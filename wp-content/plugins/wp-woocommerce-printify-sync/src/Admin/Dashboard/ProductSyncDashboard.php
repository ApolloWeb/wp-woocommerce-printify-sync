<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard;

use ApolloWeb\WPWooCommercePrintifySync\Services\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class ProductSyncDashboard
{
    use TimeStampTrait;

    private ProductSync $productSync;
    private LoggerInterface $logger;

    public function __construct(ProductSync $productSync, LoggerInterface $logger)
    {
        $this->productSync = $productSync;
        $this->logger = $logger;
    }

    public function render(): void
    {
        $stats = $this->getStats();
        $recentSyncs = $this->getRecentSyncs();
        $queuedProducts = $this->getQueuedProducts();

        include WPWPS_PLUGIN_DIR . '/templates/admin/product-sync-dashboard.php';
    }

    private function getStats(): array
    {
        return [
            'total_products' => $this->productSync->getTotalProducts(),
            'synced_today' => $this->productSync->getSyncedCount('today'),
            'failed_syncs' => $this->productSync->getFailedCount(),
            'queued_products' => $this->productSync->getQueuedCount(),
            'last_sync' => $this->productSync->getLastSyncTime(),
            'sync_success_rate' => $this->productSync->getSuccessRate()
        ];
    }

    private function getRecentSyncs(int $limit = 10): array
    {
        return $this->productSync->getRecentSyncs($limit);
    }

    private function getQueuedProducts(): array
    {
        return $this->productSync->getQueuedProducts();
    }
}